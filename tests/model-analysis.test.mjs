import test from 'node:test';
import assert from 'node:assert/strict';
import { readFile } from 'node:fs/promises';
import vm from 'node:vm';
import { createHash } from 'node:crypto';
import { analyzeMesh, exactDuplicate, remapProject, threeMFUnitScale } from '../public_html/model-analysis.js';
const attribute = values => ({ count: values.length/3, getX: i => values[i*3], getY: i => values[i*3+1], getZ: i => values[i*3+2] });
const tetra = attribute([0,0,0, 10,0,0, 0,10,0, 0,0,10]);
const indexed = values => ({ count: values.length, getX: i => values[i] });
const faces = [0,2,1, 0,1,3, 1,2,3, 2,0,3];

test('3MF declared units convert to millimeters, unknown units are not guessed', () => {
  assert.equal(threeMFUnitScale('inch'),25.4); assert.equal(threeMFUnitScale('meter'),1000);
  assert.equal(threeMFUnitScale(),1); assert.equal(threeMFUnitScale('unknown'),null);
});

test('closed indexed tetrahedron has no boundary or direction problems', () => {
  const result = analyzeMesh(tetra, indexed(faces));
  assert.equal(result.boundaryEdges,0); assert.equal(result.nonManifoldEdges,0);
  assert.equal(result.windingEdges,0); assert.equal(result.degenerateTriangles,0);
});
test('STL repeated positions weld without creating false holes', () => {
  const values = faces.flatMap(i => [tetra.getX(i),tetra.getY(i),tetra.getZ(i)]);
  assert.equal(analyzeMesh(attribute(values)).boundaryEdges,0);
});
test('removing a tetrahedron face produces three open edges', () => {
  assert.equal(analyzeMesh(tetra,indexed(faces.slice(3))).boundaryEdges,3);
});
test('flipped, duplicate and zero-area triangles are reported separately', () => {
  assert.equal(analyzeMesh(tetra,indexed([0,1,2,...faces.slice(3)])).windingEdges,3);
  const duplicate = analyzeMesh(tetra,indexed([...faces,0,2,1]));
  assert.equal(duplicate.duplicateTriangles,1); assert.equal(duplicate.nonManifoldEdges,3);
  assert.equal(analyzeMesh(attribute([0,0,0, 1,0,0, 2,0,0])).degenerateTriangles,1);
});
test('invalid indices and non-finite positions are rejected by diagnostics', () => {
  assert.equal(analyzeMesh(tetra,indexed([0,1,99])).invalidTriangles,1);
  assert.equal(analyzeMesh(attribute([NaN,0,0, 1,0,0, 0,1,0])).invalidTriangles,1);
  assert.equal(analyzeMesh(attribute([])).invalidTriangles,1);
});
test('large topology is skipped explicitly rather than sampled', () => {
  const result = analyzeMesh(tetra,indexed(faces),3);
  assert.equal(result.topologySkipped,1); assert.equal(result.boundaryEdges,0);
});
test('duplicates depend on bytes hash, not matching names', () => {
  const files = [{id:'original',name:'part.stl',hash:'content'}];
  assert.equal(exactDuplicate(files,'content').id,'original');
  assert.equal(exactDuplicate(files,'different'),null); assert.equal(exactDuplicate(files,null),null);
});
test('restored project references map to existing duplicates and new model IDs', () => {
  const project = {id:'old',name:'Robot',description:'Build',parts:[{fileId:'a',quantity:2,notes:'Left'},{fileId:'b',quantity:1,notes:''}]};
  const saved = remapProject(project,new Map([['a','existing'],['b','new']]),'new-project');
  assert.deepEqual(saved.parts.map(p=>p.fileId),['existing','new']);
  assert.equal(saved.parts[0].quantity,2); assert.equal(saved.parts[0].notes,'Left');
  assert.equal(project.parts[0].fileId,'a');
  assert.throws(()=>remapProject(project,new Map(),'x'),/ontbreekt/);
});

const archive = (await readFile(new URL('../3dlens-private/archive.php',import.meta.url),'utf8')).replace(/\r\n/g,'\n');
test('real importer reports renamed duplicates and duplicates within one batch without uploading again', async () => {
  const hash = data => createHash('sha256').update(new Uint8Array(data)).digest('hex');
  const bytes = new TextEncoder().encode('same model').buffer;
  const other = new TextEncoder().encode('different model').buffer;
  const state = {files:[{id:'original',name:'original.stl',hash:hash(bytes)}],activeCollection:null};
  let uploads=0,reports=0,serial=0;
  const context=vm.createContext({state,lastImportReport:null,hashBuffer:async data=>hash(data),exactDuplicate,
    getFileType:()=> 'stl',saveFile:async()=>{uploads++;},parseModel:()=>null,analyzeModelLocally:()=>null,
    uid:()=>`id-${++serial}`,render(){},showToast(){},showDuplicateReport(){reports++;},t:key=>key,
    setTimeout:callback=>callback(),console,alert:()=>assert.fail('Unexpected alert')});
  vm.runInContext(archive.slice(archive.indexOf('async function importSTLFiles('),archive.indexOf('async function ensureCollection(')),context);
  const file=(name,data)=>({name,size:data.byteLength,arrayBuffer:async()=>data});
  await context.importSTLFiles([file('renamed.stl',bytes),file('new.stl',other),file('copy.stl',other)]);
  assert.equal(uploads,1);assert.equal(reports,1);assert.equal(state.files.length,2);
  assert.equal(context.lastImportReport.duplicates[0].id,'original');
  assert.equal(context.lastImportReport.duplicates[1].id,state.files[1].id);
  assert.equal(context.lastImportReport.imported,1);
});
test('backup validation rejects project references before restore can write files', () => {
  const context=vm.createContext({TextEncoder,getFileType:()=> 'stl'});
  vm.runInContext(archive.slice(archive.indexOf('function validateBackup('),archive.indexOf('let pendingRestore =')),context);
  const backup={version:2,collections:[],files:[{id:'part',name:'part.stl',data:'YQ=='}],projects:[{id:'build',name:'Build',description:'',parts:[{fileId:'part',quantity:2,notes:''}]}]};
  context.validateBackup(backup);
  assert.throws(()=>context.validateBackup({...backup,projects:[{...backup.projects[0],parts:[{fileId:'missing',quantity:1,notes:''}]}]}),/Ongeldig projectonderdeel/);
  assert.throws(()=>context.validateBackup({...backup,projects:undefined}),/Projecten ontbreken/);
  context.validateBackup({version:1,collections:[],files:[]});
});

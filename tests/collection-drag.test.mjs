import { test } from 'node:test';
import assert from 'node:assert/strict';
import { readFile } from 'node:fs/promises';
import vm from 'node:vm';

const source = (await readFile(new URL('../3dlens-private/archive.php', import.meta.url), 'utf8')).replace(/\r\n/g, '\n');
const move = source.slice(source.indexOf('async function moveFileToCollection('), source.indexOf('async function deleteFile('));
const drag = source.slice(source.indexOf('// Drag & drop\n'), source.indexOf('// Recursively walk a FileSystemEntry tree'));

function fixture() {
  const listeners = {}, saved = [], imports = [];
  const state = { files: [{ id:'model', name:'part.3mf', collectionId:'old', _stored:true }], collections:[{id:'old'},{id:'new'}] };
  const context = vm.createContext({
    state, window:{addEventListener:(type, fn) => { listeners[type]=fn; }},
    document:{getElementById:()=>({classList:{add(){},remove(){}}}),querySelectorAll:()=>[]},
    saveFile:async file => { saved.push({...file}); }, render(){},
    importSTLFiles:async (files,options) => { imports.push({files,options}); },
    t:x=>x,alert(){},getFileType:()=> 'stl',
  });
  vm.runInContext(move + '\n' + drag, context);
  const drop = async (target, {internal=false, match=true, files=[]}={}) => {
    if(internal) vm.runInContext("draggedArchiveFile = 'model'", context);
    await listeners.drop({preventDefault(){},target:{closest:()=>target === undefined ? null : {dataset:{id:target}}},
      dataTransfer:{types:[internal ? 'application/x-3dlens-model' : 'Files'],items:[],files,getData:()=>match ? 'model' : 'wrong'}});
  };
  return {context,state,saved,imports,drop};
}

test('drag moves the stored model using metadata and keeps the active selection', async()=>{
  const f=fixture(); f.state.selectedFile='model';
  await f.drop('new',{internal:true});
  assert.equal(f.saved.length,1); assert.equal(f.saved[0]._stored,true);
  assert.equal(f.state.files[0].collectionId,'new'); assert.equal(f.state.selectedFile,'model');
  assert.equal(f.imports.length,0);
});
test('uncategorized clears the collection; all files and unknown targets do not move',async()=>{
  for(const target of ['',undefined,'missing','old']){
    const f=fixture(); await f.drop(target,{internal:true}); assert.equal(f.saved.length,0);
  }
  const f=fixture(); await f.drop('uncategorized',{internal:true}); assert.equal(f.saved[0].collectionId,null);
});
test('failed storage never marks a model moved locally',async()=>{
  const f=fixture(); f.context.saveFile=async()=>{throw new Error('offline');};
  await assert.rejects(f.drop('new',{internal:true}),/offline/);
  assert.equal(f.state.files[0].collectionId,'old');
});
test('a mismatched drag payload cannot move or upload models',async()=>{
  const f=fixture(); await f.drop('new',{internal:true,match:false});
  assert.equal(f.saved.length,0); assert.equal(f.imports.length,0);
});
test('external STL and 3MF files use the dropped collection, not the active view',async()=>{
  const f=fixture(); f.state.activeCollection='old';
  await f.drop('new',{files:[{name:'part.stl'},{name:'part.3mf'}]});
  assert.equal(f.imports.length,1); assert.equal(f.imports[0].options.collectionId,'new');
  assert.equal(f.saved.length,0);
});
test('external drop outside collections keeps the normal import workflow',async()=>{
  const f=fixture(); await f.drop(undefined,{files:[{name:'part.stl'}]});
  assert.equal(Object.hasOwn(f.imports[0].options,'collectionId'),false);
});

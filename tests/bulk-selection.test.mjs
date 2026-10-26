import { test } from 'node:test';
import assert from 'node:assert/strict';
import { readFile } from 'node:fs/promises';
import vm from 'node:vm';
const source = (await readFile(new URL('../3dlens-private/archive.php',import.meta.url),'utf8')).replace(/\r\n/g,'\n');
const code = source.slice(source.indexOf('// ============ BULK SELECTION ============'), source.indexOf('// ============ END BULK SELECTION ============'));
function fixture({confirm=true,failAt=0}={}) {
  const saved=[],deleted=[],messages=[];
  const state={files:[{id:'a',name:'part.stl',collectionId:'old',_stored:true},{id:'b',name:'part.3mf',collectionId:'old',_stored:true}],collections:[{id:'old'},{id:'new'}],selectedIds:new Set(['a','b']),selectedFile:'a',bulkBusy:false};
  let requests=0,confirms=0;
  const context=vm.createContext({state,document:{getElementById:()=>({textContent:''})},
    saveFile:async f=>{if(++requests===failAt)throw Error('offline');saved.push({...f});},
    dbDelete:async(store,id)=>{if(++requests===failAt)throw Error('offline');deleted.push(id);},
    geometryCache:new Map(),render(){},renderGrid(){},confirm:()=>{confirms++;return confirm;},
    showToast:(...args)=>messages.push(args),t:(key,...args)=>[key,...args].join(' '),
  });
  vm.runInContext(code+'\nrenderBulkToolbar = () => {};',context);
  return {state,saved,deleted,messages,context,confirms:()=>confirms};
}
test('bulk moves STL and 3MF with metadata only and clears successful selections',async()=>{
  const f=fixture(); await f.context.runBulkAction('move','new');
  assert.deepEqual(f.saved.map(x=>x.id),['a','b']);
  assert.ok(f.saved.every(x=>x._stored && x.collectionId==='new' && !x.data));
  assert.equal(f.state.selectedIds.size,0); assert.equal(f.state.bulkBusy,false);
});
test('a partially failed move leaves the failed and remaining files selected',async()=>{
  const f=fixture({failAt:2}); await f.context.runBulkAction('move','new');
  assert.equal(f.state.files[0].collectionId,'new'); assert.equal(f.state.files[1].collectionId,'old');
  assert.deepEqual([...f.state.selectedIds],['b']);
  assert.match(f.messages.at(-1)[0],/bulk.partial 1 2/); assert.equal(f.state.bulkBusy,false);
});
test('canceling the single bulk delete confirmation does not write anything',async()=>{
  const f=fixture({confirm:false}); await f.context.runBulkAction('delete');
  assert.equal(f.confirms(),1); assert.equal(f.deleted.length,0); assert.equal(f.state.files.length,2); assert.equal(f.state.selectedIds.size,2);
});
test('bulk deletion removes only confirmed successes and preserves a failed model',async()=>{
  const f=fixture({failAt:2}); await f.context.runBulkAction('delete');
  assert.deepEqual(f.deleted,['a']); assert.equal(f.state.files.length,1); assert.equal(f.state.files[0].id,'b');
  assert.equal(f.state.selectedFile,null); assert.deepEqual([...f.state.selectedIds],['b']); assert.equal(f.confirms(),1);
});
test('confirmed bulk deletion removes both selected models and their preview caches',async()=>{
  const f=fixture(); let disposed=0;
  f.context.geometryCache.set('a',{geometry:{dispose(){disposed++;}}});
  await f.context.runBulkAction('delete');
  assert.deepEqual(f.deleted,['a','b']); assert.equal(f.state.files.length,0);
  assert.equal(f.state.selectedIds.size,0); assert.equal(f.context.geometryCache.size,0); assert.equal(disposed,1);
});
test('bulk actions ignore removed selections, duplicates and invalid collections',async()=>{
  const f=fixture(); await f.context.runBulkAction('move','missing'); assert.equal(f.saved.length,0);
  await f.context.runBulkAction('move','__none__',['a','a','missing']);
  assert.equal(f.saved.length,1); assert.equal(f.state.files[0].collectionId,null);
});
test('an active bulk action prevents another action and selection changes',async()=>{
  const f=fixture(); f.state.bulkBusy=true;
  await f.context.runBulkAction('delete'); f.context.toggleFileSelection('a');
  assert.equal(f.confirms(),0); assert.equal(f.state.selectedIds.size,2);
});
test('toggling checkboxes adds and removes IDs without changing model data',()=>{
  const f=fixture(); f.context.toggleFileSelection('a'); assert.equal(f.state.selectedIds.has('a'),false);
  f.context.toggleFileSelection('a'); assert.equal(f.state.selectedIds.has('a'),true);
  f.context.toggleFileSelection('missing'); assert.equal(f.state.selectedIds.size,2); assert.equal(f.saved.length,0);
});

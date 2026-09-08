(function(){
'use strict';
const ctx=()=>window.HDL_BUILDER_CONTEXT;
const app=document.body;
const pageId=String(app.dataset.pageId||'0');
const storageKey='hdl-page-local-'+pageId;
let autosaveTimer=0,serverTimer=0,busy=false;

function coerceStyle(item){
  if(!item||typeof item!=='object')return{};
  const st=item.style,out={};
  if(st&&typeof st==='object'){
    Object.keys(st).forEach(k=>{
      if(/^\d+$/.test(k))return;
      const v=st[k];
      if(v!==''&&v!=null)out[k]=v;
    });
  }
  item.style=out;
  return item.style;
}
function coerceDocument(doc){
  if(!doc||typeof doc!=='object')return doc;
  (doc.sections||[]).forEach(s=>{
    coerceStyle(s);
    (s.columns||[]).forEach(c=>{
      if(c&&typeof c==='object')coerceStyle(c);
      (c.widgets||[]).forEach(w=>coerceStyle(w));
    });
  });
  return doc;
}
function applyColorFromInput(el){
  const c=ctx();if(!c||!el||el.dataset.style!=='color')return;
  const found=c.find(c.getSelected());if(!found)return;
  const style=coerceStyle(found.item);
  const hex=String(el.value||'').trim();
  if(!hex)return;
  style.color=hex;
  const canvas=document.getElementById('builder-canvas');
  const wrap=canvas?.querySelector(`.vb-widget[data-id="${found.item.id}"]`);
  const paint=wrap&&(wrap.querySelector('.demo-text, .demo-html, [data-inline], p, h1, h2, h3, h4, h5, h6')||wrap);
  if(paint){paint.style.color=hex;paint.style.setProperty('--pb-text-color',hex)}
  if(wrap)wrap.style.setProperty('--pb-text-color',hex);
  c.dirty();
  c.render(true);
}

function ensureBanner(){
  let banner=document.getElementById('hdl-recover-banner');
  if(banner)return banner;
  banner=document.createElement('div');
  banner.id='hdl-recover-banner';
  banner.className='hdl-recover-banner';
  banner.hidden=true;
  banner.innerHTML='<div><strong>Unsaved local draft found</strong><span>Recover changes from this browser before they are lost.</span></div><div class="hdl-recover-actions"><button type="button" data-recover>Recover</button><button type="button" data-dismiss class="ghost">Dismiss</button></div>';
  document.querySelector('.vb-top')?.insertAdjacentElement('afterend',banner);
  return banner;
}

function readLocal(){
  try{return JSON.parse(localStorage.getItem(storageKey)||'null')}catch(e){return null}
}
function writeLocal(){
  const c=ctx();if(!c)return;
  coerceDocument(c.getDocument());
  const payload={
    at:Date.now(),
    title:document.getElementById('page-title')?.value||'',
    slug:document.getElementById('page-slug')?.value||'',
    status:document.getElementById('page-status')?.value||'draft',
    seo_title:document.getElementById('seo-title')?.value||'',
    seo_description:document.getElementById('seo-description')?.value||'',
    document:c.getDocument()
  };
  try{localStorage.setItem(storageKey,JSON.stringify(payload))}catch(e){}
}
function clearLocal(){try{localStorage.removeItem(storageKey)}catch(e){}}

function showRecoverIfNeeded(){
  const local=readLocal();
  const c=ctx();if(!local||!c||!local.document)return;
  try{
    if(JSON.stringify(local.document)===JSON.stringify(c.getDocument()))return;
  }catch(e){return}
  const banner=ensureBanner();
  banner.hidden=false;
  banner.querySelector('[data-recover]').onclick=()=>{
    if(local.title)document.getElementById('page-title').value=local.title;
    if(local.slug)document.getElementById('page-slug').value=local.slug;
    if(local.status)document.getElementById('page-status').value=local.status;
    if(local.seo_title!=null)document.getElementById('seo-title').value=local.seo_title;
    if(local.seo_description!=null)document.getElementById('seo-description').value=local.seo_description;
    if(local.document){
      coerceDocument(local.document);
      c.setDocument(local.document);
    }
    banner.hidden=true;
  };
  banner.querySelector('[data-dismiss]').onclick=()=>{clearLocal();banner.hidden=true};
}

function scheduleLocal(){
  clearTimeout(autosaveTimer);
  autosaveTimer=setTimeout(()=>{if(ctx()?.isDirty())writeLocal()},900);
}
function scheduleServer(){
  clearTimeout(serverTimer);
  serverTimer=setTimeout(async()=>{
    const c=ctx();
    if(!c||!c.isDirty()||busy)return;
    busy=true;
    try{
      coerceDocument(c.getDocument());
      await c.save({autosave:true});
      writeLocal();
    }finally{busy=false}
  },4000);
}

function revisionList(){
  const c=ctx();if(!c)return[];
  const meta=c.getDocument().meta||{};
  return Array.isArray(meta.revisions)?meta.revisions:[];
}

function renderRevisionsPanel(){
  const host=document.getElementById('revisions-panel');
  if(!host)return;
  const list=revisionList();
  if(!list.length){
    host.innerHTML='<p class="revisions-empty">No saved revisions yet. Manual Save creates a restore point.</p>';
    return;
  }
  host.innerHTML=list.map((rev,index)=>{
    const when=rev.at?new Date(rev.at).toLocaleString():('Revision '+(index+1));
    const title=rev.title||'Untitled';
    const sections=rev.sections!=null?rev.sections:(rev.document?.sections?.length||0);
    return`<article class="revision-row"><div><strong>${escapeHtml(title)}</strong><small>${escapeHtml(when)} · ${sections} sections</small></div><button type="button" data-restore="${index}">Restore</button></article>`;
  }).join('');
  host.querySelectorAll('[data-restore]').forEach(btn=>btn.onclick=()=>{
    const rev=list[Number(btn.dataset.restore)];
    if(!rev?.document)return;
    if(!confirm('Restore this revision? Current unsaved canvas changes will be replaced.'))return;
    const c=ctx();
    const currentMeta=c.getDocument().meta||{};
    const next=JSON.parse(JSON.stringify(rev.document));
    next.meta=next.meta&&typeof next.meta==='object'?next.meta:{};
    next.meta.revisions=currentMeta.revisions||[];
    coerceDocument(next);
    c.setDocument(next);
    renderRevisionsPanel();
  });
}

function escapeHtml(value){
  const node=document.createElement('span');
  node.textContent=String(value??'');
  return node.innerHTML;
}

function patchLiveColorHandling(){
  const c=ctx();if(!c)return;
  coerceDocument(c.getDocument());
  const origSet=c.setDocument;
  if(typeof origSet==='function'&&!origSet.__hdlColorPatched){
    c.setDocument=function(next){
      coerceDocument(next);
      return origSet.call(this,next);
    };
    c.setDocument.__hdlColorPatched=true;
  }
  const origSave=c.save;
  if(typeof origSave==='function'&&!origSave.__hdlColorPatched){
    c.save=function(opts){
      coerceDocument(c.getDocument());
      return origSave.call(this,opts);
    };
    c.save.__hdlColorPatched=true;
  }
  const inspector=document.getElementById('inspector-form');
  if(inspector&&!inspector.dataset.hdlColorPatch){
    inspector.dataset.hdlColorPatch='1';
    inspector.addEventListener('input',e=>{
      const found=c.find(c.getSelected());
      if(found)coerceStyle(found.item);
    },true);
    inspector.addEventListener('change',e=>{
      const el=e.target;
      const found=c.find(c.getSelected());
      if(found)coerceStyle(found.item);
      if(el&&el.dataset&&el.dataset.style==='color')applyColorFromInput(el);
    },true);
    inspector.addEventListener('click',e=>{
      const swatch=e.target.closest('[data-text-color]');
      if(!swatch)return;
      const found=c.find(c.getSelected());
      if(!found)return;
      const hex=swatch.dataset.textColor;
      coerceStyle(found.item).color=hex;
      inspector.querySelectorAll('[data-style="color"]').forEach(input=>{
        if(input.type!=='color'||/^#[0-9a-f]{6}$/i.test(hex))input.value=hex;
      });
      applyColorFromInput(inspector.querySelector('[data-style="color"]'));
    });
  }
}

function boot(){
  if(!ctx())return;
  patchLiveColorHandling();
  showRecoverIfNeeded();
  renderRevisionsPanel();
  window.addEventListener('hdl-builder-dirty',()=>{scheduleLocal();scheduleServer()});
  window.addEventListener('hdl-builder-after-save',event=>{
    const c=ctx();
    if(c)coerceDocument(c.getDocument());
    if(!event.detail?.autosave)clearLocal();
    else writeLocal();
    renderRevisionsPanel();
  });
  document.querySelector('[data-action="revisions"]')?.addEventListener('click',()=>{
    const panel=document.getElementById('revisions-drawer');
    if(panel){panel.hidden=!panel.hidden;if(!panel.hidden)renderRevisionsPanel()}
  });
  document.querySelector('[data-close-revisions]')?.addEventListener('click',()=>{
    const panel=document.getElementById('revisions-drawer');
    if(panel)panel.hidden=true;
  });
}

if(document.readyState==='loading')document.addEventListener('DOMContentLoaded',()=>setTimeout(boot,0));
else setTimeout(boot,0);
})();

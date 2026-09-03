(function(){
'use strict';
const app=document.body,canvas=document.getElementById('builder-canvas'),inspector=document.getElementById('inspector-form');
let doc=window.HDL_PAGE_DOCUMENT||{version:2,meta:{},sections:[]};
if(!doc.meta||typeof doc.meta!=='object')doc.meta={};
let selected=null,history=[],future=[],isDirty=false,inlineEditing=false,lastSavedAt=Date.now(),saveMode='manual';
const uid=p=>p+'-'+Math.random().toString(36).slice(2,9),clone=v=>JSON.parse(JSON.stringify(v));
const esc=v=>{const e=document.createElement('div');e.textContent=v??'';return e.innerHTML};
const checked=v=>v?'checked':'';
const nl2br=v=>esc(v).replace(/\r\n|\r|\n/g,'<br>');
function clipboardToMultiline(event){
  const cd=event.clipboardData;if(!cd)return null;
  const html=cd.getData('text/html')||'';
  const plain=cd.getData('text/plain')||'';
  if(html && /<(p|div|br|li|tr|h[1-6]|blockquote)\b/i.test(html)){
    const normalized=html
      .replace(/\r\n|\r/g,'\n')
      .replace(/<br\s*\/?>/gi,'\n')
      .replace(/<\/(p|div|h[1-6]|li|blockquote|tr)>/gi,'\n');
    const box=document.createElement('div');
    box.innerHTML=normalized;
    return box.innerText.replace(/\u00a0/g,' ').replace(/\n{3,}/g,'\n\n');
  }
  if(!plain)return null;
  return plain.replace(/\r\n/g,'\n').replace(/\r/g,'\n');
}
function insertAtCursor(el,text){
  const start=el.selectionStart??el.value.length,end=el.selectionEnd??start;
  el.value=el.value.slice(0,start)+text+el.value.slice(end);
  const pos=start+text.length;
  el.selectionStart=el.selectionEnd=pos;
  el.dispatchEvent(new Event('input',{bubbles:true}));
}

const INLINE_TYPES=new Set(['heading','text','button','quote']);

function setStatus(text,cls){
  const status=document.getElementById('save-status');
  if(!status)return;
  status.textContent=text;
  status.classList.remove('is-unsaved','is-saved','is-autosaved','is-working');
  if(cls)status.classList.add(cls);
}
function dirty(){
  isDirty=true;
  setStatus('Unsaved','is-unsaved');
  window.dispatchEvent(new CustomEvent('hdl-builder-dirty'));
}
function markSaved(label,cls){
  isDirty=false;
  lastSavedAt=Date.now();
  setStatus(label||'Saved',cls||'is-saved');
  window.dispatchEvent(new CustomEvent('hdl-builder-saved',{detail:{label:label||'Saved'}}));
}
function snapshot(){history.push(clone(doc));if(history.length>60)history.shift();future=[];dirty()}

function widget(type){
  const base={id:uid('widget'),type,data:{visible:true,animation:'none',animationDelay:0,hoverEffect:'none',hideDesktop:false,hideTablet:false,hideMobile:false},style:{}};
  const values={heading:{text:'New heading',level:2},text:{text:'Write your content here.'},image:{url:'',alt:''},button:{text:'Get started',url:'#',icon:'fa-solid fa-arrow-right',variant:'primary',size:'medium',targetBlank:false},cards:{items:'fa-solid fa-star|Feature one|Description|#\nfa-solid fa-star|Feature two|Description|#\nfa-solid fa-star|Feature three|Description|#'},icon:{icon:'fa-solid fa-star'},iconbox:{icon:'fa-solid fa-star',title:'Feature title',text:'Feature description'},iconlist:{icon:'fa-solid fa-check',items:'First item\nSecond item\nThird item'},code:{language:'html',code:'<section>\n  Your code here\n</section>'},html:{html:'<h3>Custom HTML</h3>\n<p>Add safe HTML content here.</p>'},dragbox:{icon:'fa-solid fa-up-down-left-right',title:'Draggable content box',text:'Drag this widget anywhere in the canvas.'},slider:{items:'https://picsum.photos/1200/600?1|First slide|Slide description|#\nhttps://picsum.photos/1200/600?2|Second slide|Slide description|#'},tabs:{items:'Overview|Add overview content here.\nFeatures|Add feature content here.\nSupport|Add support content here.'},textpath:{text:'PROFESSIONAL DATA RECOVERY • HDD LAND •',speed:'normal'},video:{url:''},videoplaylist:{items:'Introduction|https://www.youtube.com/watch?v=dQw4w9WgXcQ\nProduct Demo|https://www.youtube.com/watch?v=dQw4w9WgXcQ'},social:{whatsapp:'',facebook:'',instagram:'',telegram:'',teams:''},pricing:{items:'Starter|$29|Per month|Feature one;Feature two|Choose Plan|#\nProfessional|$79|Per month|Everything in Starter;Priority support|Get Professional|#'},counter:{number:'100+',label:'Successful projects'},quote:{text:'Customer testimonial',author:'Customer name'}};
  Object.assign(base.data,values[type]||{});if(type==='spacer')base.style.minHeight='40px';return base;
}
function section(){return{id:uid('section'),data:{visible:true,animation:'none',animationDelay:0,hoverEffect:'none',hideDesktop:false,hideTablet:false,hideMobile:false},style:{padding:'70px',backgroundColor:'#ffffff',backgroundSize:'cover',backgroundPosition:'center'},columns:[{id:uid('column'),widgets:[]}]}}
function find(id){for(const s of doc.sections){if(s.id===id)return{kind:'section',item:s};for(const c of s.columns||[])for(const w of c.widgets||[])if(w.id===id)return{kind:'widget',item:w,section:s,column:c}}return null}
function remove(id){for(let si=0;si<doc.sections.length;si++){if(doc.sections[si].id===id){doc.sections.splice(si,1);return}for(const c of doc.sections[si].columns){const i=c.widgets.findIndex(w=>w.id===id);if(i>=0){c.widgets.splice(i,1);return}}}}
function css(item){const st=item.style||{};return Object.entries(st).filter(([,v])=>v!==''&&v!=null).map(([k,v])=>{if(k==='fontSize')return`--pb-font-size-desktop:${v};font-size:${v}`;if(k==='fontSizeTablet')return`--pb-font-size-tablet:${v}`;if(k==='fontSizeMobile')return`--pb-font-size-mobile:${v}`;let name=k.replace(/[A-Z]/g,m=>'-'+m.toLowerCase());if(k==='backgroundImage')v=`url("${String(v).replace(/["()]/g,'')}")`;return name+':'+v}).join(';')}
function stateClasses(item){const d=item.data||{},out=[];if(d.visible===false)out.push('is-hidden');['Desktop','Tablet','Mobile'].forEach(x=>{if(d['hide'+x])out.push('hide-'+x.toLowerCase())});if(d.animation&&d.animation!=='none')out.push('fx-'+d.animation);if(d.hoverEffect&&d.hoverEffect!=='none')out.push('hover-'+d.hoverEffect);return out.join(' ')}

function preview(w){
  const d=w.data||{},style=css(w),cl=stateClasses(w);
  if(w.type==='heading'){const n=Math.max(1,Math.min(6,Number(d.level)||2));return`<h${n} class="${cl}" style="${style}"><span data-inline="text">${esc(d.text||'Heading')}</span></h${n}>`}
  if(w.type==='text')return`<p class="${cl}" style="${style};white-space:pre-wrap"><span data-inline="text">${nl2br(d.text||'Text')}</span></p>`;
  if(w.type==='image')return d.url?`<img class="${cl}" style="${style}" src="${esc(d.url)}" alt="${esc(d.alt||'')}">`:'<div class="widget-placeholder">Enter an image URL</div>';
  if(w.type==='button')return`<span class="demo-button ${cl}" style="${style}">${d.icon?`<i class="${esc(d.icon)}"></i> `:''}<span data-inline="text">${esc(d.text||'Button')}</span></span>`;
  if(w.type==='cards')return`<div class="demo-cards ${cl}" style="${style}"><span>Feature one</span><span>Feature two</span><span>Feature three</span></div>`;
  if(w.type==='icon')return`<div class="demo-icon ${cl}" style="${style}"><i class="${esc(d.icon)}"></i></div>`;
  if(w.type==='iconbox')return`<div class="demo-iconbox ${cl}" style="${style}"><i class="${esc(d.icon)}"></i><h3>${esc(d.title)}</h3><p>${esc(d.text)}</p></div>`;
  if(w.type==='iconlist')return`<ul class="demo-list ${cl}" style="${style}">${String(d.items||'').split(/\n/).filter(Boolean).map(x=>`<li><i class="${esc(d.icon)}"></i>${esc(x)}</li>`).join('')}</ul>`;
  if(w.type==='code')return`<pre class="demo-code ${cl}" style="${style}"><code>${esc(d.code)}</code></pre>`;
  if(w.type==='html')return`<div class="widget-placeholder ${cl}" style="${style}"><i class="fa-solid fa-file-code"></i> Custom HTML block</div>`;
  if(w.type==='dragbox')return`<div class="demo-iconbox ${cl}" style="${style}"><i class="${esc(d.icon)}"></i><h3>${esc(d.title)}</h3><p>${esc(d.text)}</p></div>`;
  if(w.type==='slider')return`<div class="demo-slider ${cl}" style="${style}"><i class="fa-solid fa-images"></i><strong>Professional Slider</strong><span>${String(d.items||'').split(/\n/).filter(Boolean).length} slides</span></div>`;
  if(w.type==='tabs')return`<div class="demo-tabs ${cl}" style="${style}">${String(d.items||'').split(/\n/).filter(Boolean).map((x,i)=>`<span class="${i?'':'active'}">${esc(x.split('|')[0])}</span>`).join('')}</div>`;
  if(w.type==='textpath')return`<div class="demo-textpath ${cl}" style="${style}">${esc(d.text)}</div>`;
  if(w.type==='counter')return`<div class="demo-counter ${cl}" style="${style}"><strong>${esc(d.number)}</strong><span>${esc(d.label)}</span></div>`;
  if(w.type==='quote')return`<blockquote class="${cl}" style="${style};white-space:pre-wrap"><span data-inline="text">${nl2br(d.text)}</span><cite data-inline="author">${esc(d.author)}</cite></blockquote>`;
  if(w.type==='video'||w.type==='videoplaylist')return`<div class="widget-placeholder ${cl}" style="${style}"><i class="fa-solid fa-play"></i> ${w.type==='videoplaylist'?'Video Playlist':(d.url?'Video is ready to preview':'Enter a video URL')}</div>`;
  if(w.type==='social')return`<div class="demo-social ${cl}" style="${style}">${['whatsapp','facebook','instagram','telegram','teams'].map(x=>`<i class="fa-brands fa-${x==='teams'?'microsoft':x}"></i>`).join('')}</div>`;
  if(w.type==='pricing')return`<div class="demo-pricing ${cl}" style="${style}"><strong>Pricing Tables</strong><span>${String(d.items||'').split(/\n/).filter(Boolean).length} plans</span></div>`;
  if(w.type==='divider')return`<div class="${cl}" style="${style}"><hr></div>`;
  return`<div class="${cl}" style="${style}"></div>`;
}

function render(keepInspector=false){
  if(inlineEditing)return;
  canvas.innerHTML='';
  if(!doc.sections.length){
    canvas.innerHTML='<div class="canvas-empty"><div><h2>Start building your page</h2><p>Add a section, press <kbd>/</kbd> or <kbd>Ctrl</kbd>+<kbd>K</kbd> for quick insert.</p><button id="add-first">Add first section</button><button type="button" id="open-patterns" class="ghost">Browse patterns</button></div></div>';
    document.getElementById('add-first').onclick=()=>{snapshot();doc.sections.push(section());render()};
    document.getElementById('open-patterns')?.addEventListener('click',()=>window.dispatchEvent(new CustomEvent('hdl-open-patterns')));
    if(!keepInspector)renderInspector();
    return;
  }
  doc.sections.forEach(s=>{
    const el=document.createElement('section');
    el.className='vb-section '+stateClasses(s)+(selected===s.id?' selected':'');
    el.dataset.id=s.id;el.draggable=true;el.style.cssText=css(s);
    el.innerHTML='<div class="vb-section-toolbar"><button data-cmd="move">☰</button><button data-cmd="duplicate">⧉</button><button data-cmd="delete">×</button></div><div class="vb-columns cols-'+s.columns.length+'"></div>';
    const cols=el.querySelector('.vb-columns');
    s.columns.forEach(c=>{
      const col=document.createElement('div');col.className='vb-column';col.dataset.column=c.id;
      c.widgets.forEach(w=>{
        const we=document.createElement('div');
        we.className='vb-widget '+(selected===w.id?'selected ':'')+stateClasses(w)+(INLINE_TYPES.has(w.type)?' is-inlineable':'');
        we.draggable=true;we.dataset.id=w.id;we.dataset.type=w.type;
        we.innerHTML='<div class="vb-widget-toolbar"><button data-cmd="duplicate">⧉</button><button data-cmd="delete">×</button></div>'+preview(w);
        col.appendChild(we);
      });
      cols.appendChild(col);
    });
    canvas.appendChild(el);
  });
  if(!keepInspector)renderInspector();
}

function contentFields(i,kind){
  const d=i.data||{};
  if(kind==='section')return`<label>Columns<select data-section-columns>${[1,2,3,4].map(n=>`<option value="${n}" ${i.columns.length===n?'selected':''}>${n}</option>`).join('')}</select></label>`;
  const fields={
    heading:`<label>Heading text<textarea data-data="text">${esc(d.text)}</textarea></label><label>HTML level<select data-data="level">${[1,2,3,4,5,6].map(n=>`<option value="${n}" ${Number(d.level)===n?'selected':''}>H${n}</option>`).join('')}</select></label><small class="inline-hint">Double-click heading on canvas to edit inline.</small>`,
    text:`<label>Text<textarea rows="7" data-data="text">${esc(d.text)}</textarea></label><small class="inline-hint">Double-click text on canvas to edit inline.</small>`,
    image:`<label>Image URL<input data-data="url" value="${esc(d.url)}"></label><label>Alt text<input data-data="alt" value="${esc(d.alt)}"></label>`,
    button:`<label>Button text<input data-data="text" value="${esc(d.text)}"></label><label>Link URL<input data-data="url" value="${esc(d.url)}"></label><label>Icon class<input data-data="icon" value="${esc(d.icon)}"></label><div class="two-cols"><label>Style<select data-data="variant">${['primary','secondary','outline','dark','light'].map(v=>`<option ${d.variant===v?'selected':''}>${v}</option>`).join('')}</select></label><label>Size<select data-data="size">${['small','medium','large'].map(v=>`<option ${d.size===v?'selected':''}>${v}</option>`).join('')}</select></label></div><label class="switch-row"><input type="checkbox" data-bool="targetBlank" ${checked(d.targetBlank)}> Open in new tab</label>`,
    cards:`<label>Icon | Title | Description | URL<textarea rows="9" data-data="items">${esc(d.items)}</textarea></label>`,
    icon:`<label>Font Awesome class<input data-data="icon" value="${esc(d.icon)}"></label>`,
    iconbox:`<label>Icon class<input data-data="icon" value="${esc(d.icon)}"></label><label>Title<input data-data="title" value="${esc(d.title)}"></label><label>Description<textarea data-data="text">${esc(d.text)}</textarea></label>`,
    iconlist:`<label>Icon class<input data-data="icon" value="${esc(d.icon)}"></label><label>Items (one per line)<textarea rows="7" data-data="items">${esc(d.items)}</textarea></label>`,
    code:`<label>Language<input data-data="language" value="${esc(d.language)}" placeholder="html"></label><label>Code<textarea rows="12" data-data="code">${esc(d.code)}</textarea></label>`,
    html:`<label>Safe HTML<textarea rows="12" data-data="html">${esc(d.html)}</textarea></label><small>Scripts, event handlers and unsafe tags are removed on the public page.</small>`,
    dragbox:`<label>Icon class<input data-data="icon" value="${esc(d.icon)}"></label><label>Title<input data-data="title" value="${esc(d.title)}"></label><label>Text<textarea data-data="text">${esc(d.text)}</textarea></label>`,
    slider:`<label>Image URL | Title | Description | Link<textarea rows="11" data-data="items">${esc(d.items)}</textarea></label>`,
    tabs:`<label>Tab title | Content<textarea rows="10" data-data="items">${esc(d.items)}</textarea></label>`,
    textpath:`<label>Animated text<input data-data="text" value="${esc(d.text)}"></label><label>Speed<select data-data="speed">${['slow','normal','fast'].map(v=>`<option ${d.speed===v?'selected':''}>${v}</option>`).join('')}</select></label>`,
    counter:`<label>Number<input data-data="number" value="${esc(d.number)}"></label><label>Label<input data-data="label" value="${esc(d.label)}"></label>`,
    quote:`<label>Testimonial<textarea data-data="text">${esc(d.text)}</textarea></label><label>Author<input data-data="author" value="${esc(d.author)}"></label>`,
    video:`<label>YouTube or video file URL<input data-data="url" value="${esc(d.url)}"></label>`,
    videoplaylist:`<label>Video title | YouTube or file URL<textarea rows="11" data-data="items">${esc(d.items)}</textarea></label>`,
    social:`<label>WhatsApp URL<input data-data="whatsapp" value="${esc(d.whatsapp)}"></label><label>Facebook URL<input data-data="facebook" value="${esc(d.facebook)}"></label><label>Instagram URL<input data-data="instagram" value="${esc(d.instagram)}"></label><label>Telegram URL<input data-data="telegram" value="${esc(d.telegram)}"></label><label>Microsoft Teams URL<input data-data="teams" value="${esc(d.teams)}"></label>`,
    pricing:`<label>Name | Price | Period | Features separated by ; | Button | URL<textarea rows="12" data-data="items">${esc(d.items)}</textarea></label>`
  };
  return fields[i.type]||'';
}

function alignButtons(value){return`<div class="align-buttons">${[['right','fa-align-right'],['center','fa-align-center'],['left','fa-align-left'],['justify','fa-align-justify']].map(([v,ic])=>`<button type="button" data-style-button="textAlign" data-value="${v}" class="${value===v?'active':''}" title="${v}"><i class="fa-solid ${ic}"></i></button>`).join('')}</div>`}
function openIconPicker(input){const icons=window.HDL_ICON_CATALOG||[];if(!icons.length)return;const modal=document.createElement('div');modal.className='builder-icon-modal';modal.innerHTML='<div><header><strong>Visual Icon Library</strong><button type="button" data-close>×</button></header><input type="search" placeholder="Search 150+ icons" data-search><section></section></div>';document.body.appendChild(modal);const grid=modal.querySelector('section'),search=modal.querySelector('[data-search]');function draw(){const q=search.value.trim().toLowerCase();grid.innerHTML=icons.filter(x=>!q||x.name.includes(q)||x.category.toLowerCase().includes(q)).map(x=>`<button type="button" data-icon="${x.className}" title="${x.category} — ${x.name}"><i class="${x.className}"></i><span>${x.name}</span></button>`).join('')}draw();search.oninput=draw;modal.onclick=e=>{if(e.target===modal||e.target.closest('[data-close]'))modal.remove();const button=e.target.closest('[data-icon]');if(button){input.value=button.dataset.icon;input.dispatchEvent(new Event('input',{bubbles:true}));modal.remove()}};search.focus()}
function enhanceTypographyControls(style){
  const details=[...inspector.querySelectorAll('details')].find(x=>x.querySelector('summary')?.textContent.trim()==='Typography & Alignment');if(!details)return;
  const oldSize=details.querySelector('[data-style="fontSize"]');if(oldSize?.parentElement)oldSize.parentElement.hidden=true;
  const oldColor=inspector.querySelector('details:nth-of-type(3) [data-style="color"]');if(oldColor?.parentElement)oldColor.parentElement.hidden=true;
  const sizes=['','12px','14px','16px','18px','20px','24px','28px','32px','36px','42px','48px','56px','64px','72px'];
  const rows=[['fontSize','Desktop','fa-desktop'],['fontSizeTablet','Tablet','fa-tablet-screen-button'],['fontSizeMobile','Mobile','fa-mobile-screen-button']].map(([key,label,icon])=>{const value=String(style[key]||'');return`<div class="typography-size-row"><span><i class="fa-solid ${icon}"></i>${label}</span><select data-font-preset="${key}">${sizes.map(v=>`<option value="${v}" ${v===value?'selected':''}>${v||'Theme default'}</option>`).join('')}<option value="custom" ${value&&!sizes.includes(value)?'selected':''}>Custom</option></select><input data-style="${key}" value="${esc(value)}" placeholder="e.g. 18px"></div>`}).join('');
  const color=String(style.color||'#08142d');const palette=['#08142d','#ffffff','#d40012','#006cff','#1a8b55','#6b7280','#f59e0b','#7c3aed'];
  const panel=document.createElement('div');panel.className='typography-pro';panel.innerHTML=`<div class="typography-pro-title"><strong>Selected element typography</strong><small>Responsive font sizes and text color</small></div><div class="typography-device-sizes">${rows}</div><label class="typography-color-label">Selected element text color</label><div class="typography-color-control"><input type="color" data-style="color" value="${/^#[0-9a-f]{6}$/i.test(color)?color:'#08142d'}"><input data-style="color" value="${esc(color)}" placeholder="#08142d"></div><div class="typography-palette">${palette.map(v=>`<button type="button" data-text-color="${v}" style="--swatch:${v}" title="${v}" class="${v.toLowerCase()===color.toLowerCase()?'active':''}"></button>`).join('')}</div>`;
  const family=details.querySelector('[data-style="fontFamily"]')?.closest('label');(family||details.querySelector('summary')).insertAdjacentElement('afterend',panel);
  panel.querySelectorAll('[data-font-preset]').forEach(select=>select.addEventListener('change',()=>{const input=panel.querySelector(`[data-style="${select.dataset.fontPreset}"]`);if(select.value!=='custom'){input.value=select.value;input.dispatchEvent(new Event('input',{bubbles:true}))}else input.focus()}));
  const colorInputs=[...panel.querySelectorAll('[data-style="color"]')];colorInputs.forEach(input=>input.addEventListener('input',()=>{const value=input.value.trim();colorInputs.forEach(other=>{if(other!==input&&(other.type!=='color'||/^#[0-9a-f]{6}$/i.test(value)))other.value=value});panel.querySelectorAll('[data-text-color]').forEach(button=>button.classList.toggle('active',button.dataset.textColor.toLowerCase()===value.toLowerCase()))}));
  panel.querySelectorAll('[data-text-color]').forEach(button=>button.addEventListener('click',()=>{const inputs=panel.querySelectorAll('[data-style="color"]');inputs.forEach(input=>input.value=button.dataset.textColor);inputs[0].dispatchEvent(new Event('input',{bubbles:true}));panel.querySelectorAll('[data-text-color]').forEach(x=>x.classList.toggle('active',x===button))}));
}

function renderInspector(){
  const f=find(selected);
  document.querySelector('.inspector-empty').hidden=!!f;
  inspector.hidden=!f;
  if(!f)return;
  const i=f.item,d=i.data||(i.data={}),s=i.style||(i.style={});
  const title=f.kind==='section'?'Section':({heading:'Heading',text:'Text',image:'Image',button:'Professional Button',cards:'Cards',icon:'Icon',iconbox:'Icon Box',iconlist:'Icon List',code:'Code',html:'HTML',dragbox:'Drag Widget',slider:'Professional Slider',tabs:'Tabs',textpath:'Text Path',counter:'Counter',quote:'Testimonial',video:'Video',videoplaylist:'Video Playlist',social:'Social Links',pricing:'Price Table',spacer:'Spacer',divider:'Divider'}[i.type]||i.type);
  inspector.innerHTML=`<h2>${title}</h2><details open><summary>Content</summary>${contentFields(i,f.kind)}<label class="switch-row"><input type="checkbox" data-bool="visible" ${checked(d.visible!==false)}> ${i.type==='heading'?'Show heading':'Show element'}</label></details>
  <details open><summary>Typography & Alignment</summary>${alignButtons(s.textAlign||'')}<label>Font family<select data-style="fontFamily"><option value="">Theme font</option>${['Tahoma, Arial, sans-serif','Arial, sans-serif','Inter, Arial, sans-serif','Verdana, sans-serif','Georgia, serif','Times New Roman, serif'].map(v=>`<option value="${v}" ${s.fontFamily===v?'selected':''}>${v.split(',')[0]}</option>`).join('')}</select></label><div class="two-cols"><label>Font size<input data-style="fontSize" value="${esc(s.fontSize||'')}" placeholder="18px"></label><label>Font weight<select data-style="fontWeight"><option value="">Default</option>${[300,400,500,600,700,800,900].map(v=>`<option ${String(s.fontWeight)===String(v)?'selected':''}>${v}</option>`).join('')}</select></label><label>Line height<input data-style="lineHeight" value="${esc(s.lineHeight||'')}" placeholder="1.8"></label><label>Letter spacing<input data-style="letterSpacing" value="${esc(s.letterSpacing||'')}" placeholder="0px"></label></div><div class="two-cols"><label>Font style<select data-style="fontStyle"><option value="">Default</option><option value="normal" ${s.fontStyle==='normal'?'selected':''}>Normal</option><option value="italic" ${s.fontStyle==='italic'?'selected':''}>Italic</option></select></label><label>Text transform<select data-style="textTransform"><option value="">None</option><option value="uppercase" ${s.textTransform==='uppercase'?'selected':''}>Uppercase</option><option value="lowercase" ${s.textTransform==='lowercase'?'selected':''}>Lowercase</option><option value="capitalize" ${s.textTransform==='capitalize'?'selected':''}>Capitalize</option></select></label></div></details>
  <details><summary>Color, Background & Spacing</summary><div class="two-cols"><label>Text color<input type="color" data-style="color" value="${s.color||'#08142d'}"></label><label>Background color<input type="color" data-style="backgroundColor" value="${s.backgroundColor||'#ffffff'}"></label></div><label>Background image URL<input data-style="backgroundImage" value="${esc(s.backgroundImage||'')}"></label><div class="two-cols"><label>Background size<select data-style="backgroundSize"><option value="cover" ${s.backgroundSize==='cover'?'selected':''}>Cover</option><option value="contain" ${s.backgroundSize==='contain'?'selected':''}>Contain</option><option value="auto" ${s.backgroundSize==='auto'?'selected':''}>Auto</option></select></label><label>Position<select data-style="backgroundPosition">${['center','top','bottom','right','left'].map(v=>`<option ${s.backgroundPosition===v?'selected':''}>${v}</option>`).join('')}</select></label><label>Padding<input data-style="padding" value="${esc(s.padding||'')}" placeholder="30px"></label><label>Margin<input data-style="margin" value="${esc(s.margin||'')}" placeholder="0px"></label><label>Minimum height<input data-style="minHeight" value="${esc(s.minHeight||'')}" placeholder="200px"></label><label>Maximum width<input data-style="maxWidth" value="${esc(s.maxWidth||'')}" placeholder="1200px"></label></div></details>
  <details><summary>Border & Shadow</summary><div class="two-cols"><label>Border width<input data-style="borderWidth" value="${esc(s.borderWidth||'')}" placeholder="1px"></label><label>Border style<select data-style="borderStyle"><option value="">None</option>${['solid','dashed','dotted','double','none'].map(v=>`<option ${s.borderStyle===v?'selected':''}>${v}</option>`).join('')}</select></label><label>Border color<input type="color" data-style="borderColor" value="${s.borderColor||'#cbd5e1'}"></label><label>Border radius<input data-style="borderRadius" value="${esc(s.borderRadius||'')}" placeholder="12px"></label></div><label>Box shadow<input data-style="boxShadow" value="${esc(s.boxShadow||'')}" placeholder="0 12px 30px rgba(0,0,0,.15)"></label><label>Opacity<input type="range" min="0" max="1" step="0.05" data-style="opacity" value="${s.opacity||1}"></label></details>
  <details open><summary>Effects & Responsive</summary><label>Entrance animation<select data-data="animation"><option value="none">None</option>${[['fade-in','Fade In'],['fade-up','Fade Up'],['slide-right','Slide Right'],['slide-left','Slide Left'],['zoom-in','Zoom In']].map(([v,t])=>`<option value="${v}" ${d.animation===v?'selected':''}>${t}</option>`).join('')}</select></label><label>Animation delay (ms)<input type="number" min="0" max="3000" step="100" data-data="animationDelay" value="${Number(d.animationDelay)||0}"></label><label>Hover effect<select data-data="hoverEffect"><option value="none">None</option>${[['lift','Lift'],['grow','Grow'],['glow','Glow'],['fade','Fade']].map(([v,t])=>`<option value="${v}" ${d.hoverEffect===v?'selected':''}>${t}</option>`).join('')}</select></label><div class="visibility-grid"><label><input type="checkbox" data-bool="hideDesktop" ${checked(d.hideDesktop)}> Hide on desktop</label><label><input type="checkbox" data-bool="hideTablet" ${checked(d.hideTablet)}> Hide on tablet</label><label><input type="checkbox" data-bool="hideMobile" ${checked(d.hideMobile)}> Hide on mobile</label></div></details><div class="inspector-actions"><button data-inspector="duplicate">Duplicate</button><button class="delete" data-inspector="delete">Delete</button></div>`;
  inspector.querySelectorAll('input[data-data="icon"]').forEach(input=>{const button=document.createElement('button');button.type='button';button.className='builder-icon-browse';button.innerHTML='<i class="'+esc(input.value||'fa-solid fa-icons')+'"></i> Browse visual icons';button.onclick=()=>openIconPicker(input);input.insertAdjacentElement('afterend',button)});
  enhanceTypographyControls(s);
}

function startInlineEdit(node,widgetEl){
  const field=node.dataset.inline;if(!field)return;
  const id=widgetEl.dataset.id,f=find(id);if(!f||f.kind!=='widget')return;
  const original=String(f.item.data[field]??'');
  const before=clone(doc);
  inlineEditing=true;selected=id;
  widgetEl.classList.add('selected','is-inline-editing');
  node.contentEditable='true';node.classList.add('is-editing');
  node.focus();
  const range=document.createRange();range.selectNodeContents(node);const sel=window.getSelection();sel.removeAllRanges();sel.addRange(range);
  const syncInspector=()=>{
    const input=inspector.querySelector(`[data-data="${field}"]`);
    if(input)input.value=node.innerText;
  };
  const onInput=()=>{f.item.data[field]=node.innerText;dirty();syncInspector()};
  const finish=(commit)=>{
    node.removeEventListener('input',onInput);
    node.removeEventListener('keydown',onKey);
    node.removeEventListener('paste',onPaste);
    node.removeEventListener('blur',onBlur);
    node.contentEditable='false';
    node.classList.remove('is-editing');
    widgetEl.classList.remove('is-inline-editing');
    inlineEditing=false;
    if(!commit){f.item.data[field]=original;node.textContent=original}
    else if(String(f.item.data[field]??'')!==original){history.push(before);if(history.length>60)history.shift();future=[];dirty()}
    render();
  };
  const onKey=e=>{
    if(e.key==='Escape'){e.preventDefault();finish(false);return}
    if(e.key==='Enter'&&(f.item.type==='heading'||f.item.type==='button'||field==='author')){e.preventDefault();finish(true)}
  };
  const onPaste=e=>{
    if(f.item.type!=='text'&&f.item.type!=='quote')return;
    const text=clipboardToMultiline(e);if(text==null)return;
    e.preventDefault();
    document.execCommand('insertText',false,text);
  };
  const onBlur=()=>setTimeout(()=>{if(inlineEditing)finish(true)},80);
  node.addEventListener('input',onInput);
  node.addEventListener('keydown',onKey);
  node.addEventListener('paste',onPaste);
  node.addEventListener('blur',onBlur);
  renderInspector();
}

canvas.addEventListener('click',e=>{
  if(inlineEditing)return;
  const target=e.target.closest('[data-id]');if(!target)return;
  const cmd=e.target.closest('[data-cmd]')?.dataset.cmd,id=target.dataset.id;
  if(cmd==='delete'){snapshot();remove(id);selected=null;render();return}
  if(cmd==='duplicate'){duplicate(id);return}
  selected=id;render();
});
canvas.addEventListener('dblclick',e=>{
  const inline=e.target.closest('[data-inline]');
  const widgetEl=e.target.closest('.vb-widget');
  if(!inline||!widgetEl)return;
  e.preventDefault();e.stopPropagation();
  if(selected!==widgetEl.dataset.id){selected=widgetEl.dataset.id;render();requestAnimationFrame(()=>{const again=canvas.querySelector(`.vb-widget[data-id="${widgetEl.dataset.id}"] [data-inline="${inline.dataset.inline}"]`);if(again)startInlineEdit(again,again.closest('.vb-widget'))});return}
  startInlineEdit(inline,widgetEl);
});

function duplicate(id){const f=find(id);if(!f)return;snapshot();if(f.kind==='section'){const cp=clone(f.item);cp.id=uid('section');(cp.columns||[]).forEach(c=>{c.id=uid('column');(c.widgets||[]).forEach(w=>w.id=uid('widget'))});doc.sections.splice(doc.sections.indexOf(f.item)+1,0,cp)}else{const cp=clone(f.item);cp.id=uid('widget');f.column.widgets.splice(f.column.widgets.indexOf(f.item)+1,0,cp)}render()}
inspector.addEventListener('focusin',e=>{if(e.target.matches('input,textarea,select')){history.push(clone(doc));if(history.length>60)history.shift();future=[]}});
inspector.addEventListener('input',e=>{const f=find(selected);if(!f)return;const el=e.target;if(el.dataset.data)f.item.data[el.dataset.data]=el.type==='number'?Number(el.value):el.value;else if(el.dataset.style)f.item.style[el.dataset.style]=el.value;else if(el.dataset.bool)f.item.data[el.dataset.bool]=el.checked;else if(el.hasAttribute('data-section-columns')){const n=Number(el.value);while(f.item.columns.length<n)f.item.columns.push({id:uid('column'),widgets:[]});f.item.columns=f.item.columns.slice(0,n)}dirty();render(true)});
inspector.addEventListener('paste',e=>{const el=e.target;if(!el.matches('textarea[data-data="text"],textarea[data-data="html"]'))return;const text=clipboardToMultiline(e);if(text==null)return;e.preventDefault();insertAtCursor(el,text);});
inspector.addEventListener('click',e=>{const styleButton=e.target.closest('[data-style-button]');if(styleButton){const f=find(selected);snapshot();f.item.style[styleButton.dataset.styleButton]=styleButton.dataset.value;dirty();render();return}const cmd=e.target.closest('[data-inspector]')?.dataset.inspector;if(!cmd)return;if(cmd==='delete'){snapshot();remove(selected);selected=null;render()}else duplicate(selected)});
document.querySelectorAll('[data-widget]').forEach(b=>{b.addEventListener('click',()=>add(b.dataset.widget));b.addEventListener('dragstart',e=>e.dataTransfer.setData('widget',b.dataset.widget))});
function add(type){snapshot();if(type==='section')doc.sections.push(section());else{if(!doc.sections.length)doc.sections.push(section());doc.sections.at(-1).columns[0].widgets.push(widget(type))}selected=doc.sections.at(-1).columns[0].widgets.at(-1)?.id||selected;render()}
function insertSection(sectionObj){snapshot();const cp=clone(sectionObj);cp.id=uid('section');(cp.columns||[]).forEach(c=>{c.id=uid('column');(c.widgets||[]).forEach(w=>w.id=uid('widget'))});doc.sections.push(cp);selected=cp.id;render()}
function setDocument(next){doc=clone(next);if(!doc.meta||typeof doc.meta!=='object')doc.meta={};selected=null;inlineEditing=false;render();dirty()}

canvas.addEventListener('dragstart',e=>{if(inlineEditing){e.preventDefault();return}const w=e.target.closest('.vb-widget'),s=e.target.closest('.vb-section');if(w){e.stopPropagation();e.dataTransfer.setData('move-widget',w.dataset.id)}else if(s)e.dataTransfer.setData('move-section',s.dataset.id)});
canvas.addEventListener('dragover',e=>e.preventDefault());
canvas.addEventListener('drop',e=>{e.preventDefault();const type=e.dataTransfer.getData('widget');if(type){add(type);return}const wid=e.dataTransfer.getData('move-widget');if(wid){const source=find(wid),targetWidget=e.target.closest('.vb-widget'),targetColumn=e.target.closest('.vb-column');if(!source||!targetColumn)return;const targetSection=find(targetColumn.closest('.vb-section').dataset.id).item,targetCol=targetSection.columns.find(c=>c.id===targetColumn.dataset.column);snapshot();source.column.widgets.splice(source.column.widgets.indexOf(source.item),1);const index=targetWidget?targetCol.widgets.findIndex(w=>w.id===targetWidget.dataset.id):targetCol.widgets.length;targetCol.widgets.splice(Math.max(0,index),0,source.item);render();return}const sid=e.dataTransfer.getData('move-section');if(sid){const source=find(sid)?.item,target=e.target.closest('.vb-section');if(!source||!target||source.id===target.dataset.id)return;snapshot();doc.sections.splice(doc.sections.indexOf(source),1);doc.sections.splice(Math.max(0,doc.sections.findIndex(s=>s.id===target.dataset.id)),0,source);render()}});
document.querySelectorAll('[data-device]').forEach(b=>b.onclick=()=>{document.querySelectorAll('[data-device]').forEach(x=>x.classList.remove('active'));b.classList.add('active');canvas.className='vb-canvas '+b.dataset.device});
document.querySelector('[data-action="undo"]').onclick=()=>{if(!history.length)return;future.push(clone(doc));doc=history.pop();selected=null;render();dirty()};
document.querySelector('[data-action="redo"]').onclick=()=>{if(!future.length)return;history.push(clone(doc));doc=future.pop();selected=null;render();dirty()};
function updateUrl(){const slug=document.getElementById('page-slug').value.trim(),path='/page.php?slug='+encodeURIComponent(slug);document.getElementById('page-url').value=path;document.getElementById('preview-link').href='..'+path}
const slugInput=document.getElementById('page-slug'),slugUnlock=document.getElementById('unlock-page-slug');
slugInput.addEventListener('input',updateUrl);
slugUnlock?.addEventListener('change',()=>{slugInput.readOnly=!slugUnlock.checked;if(slugUnlock.checked){slugInput.focus();alert('Changing this URL will synchronize managed menu and product links. Continue only when the address must change.')}});
document.querySelector('[data-action="copy-url"]').onclick=async()=>{const input=document.getElementById('page-url'),full=new URL(input.value,location.origin).href;try{await navigator.clipboard.writeText(full);setStatus('URL copied','is-saved')}catch(e){input.select();document.execCommand('copy')}};
document.getElementById('widget-search').addEventListener('input',e=>{const q=e.target.value.trim().toLowerCase();document.querySelectorAll('[data-widget]').forEach(b=>b.hidden=q&&!b.textContent.toLowerCase().includes(q)&&!b.dataset.widget.includes(q))});

function syncPageMetaFromForm(){
  if(!doc.meta||typeof doc.meta!=='object')doc.meta={};
  const robots=document.getElementById('seo-robots');
  if(robots)doc.meta.seoRobots=robots.value||'';
}

async function save(options={}){
  const autosave=!!options.autosave;
  const button=document.querySelector('[data-action="save"]'),status=document.getElementById('save-status');
  if(!autosave&&button)button.disabled=true;
  setStatus(autosave?'Autosaving…':'Saving…','is-working');
  syncPageMetaFromForm();
  try{
    const payload={
      csrf:app.dataset.csrf,
      id:Number(app.dataset.pageId),
      title:document.getElementById('page-title').value,
      slug:document.getElementById('page-slug').value,
      allow_slug_change:!!document.getElementById('unlock-page-slug')?.checked,
      status:document.getElementById('page-status').value,
      seo_title:document.getElementById('seo-title').value,
      seo_description:document.getElementById('seo-description').value,
      document:doc,
      autosave
    };
    const r=await fetch(app.dataset.api+(autosave?'?action=autosave':''),{method:'POST',headers:{'Content-Type':'application/json','Accept':'application/json'},body:JSON.stringify(payload)});
    const out=await r.json();
    if(!r.ok||!out.ok)throw new Error(out.error||'Save failed');
    if(out.slug)document.getElementById('page-slug').value=out.slug;
    if(out.document&&typeof out.document==='object'){doc=out.document;if(!doc.meta)doc.meta={}}
    document.getElementById('unlock-page-slug').checked=false;
    document.getElementById('page-slug').readOnly=true;
    if(!autosave){history=[];future=[]}
    markSaved(autosave?'Autosaved':'Saved',autosave?'is-autosaved':'is-saved');
    updateUrl();
    window.dispatchEvent(new CustomEvent('hdl-builder-after-save',{detail:{autosave,document:doc}}));
    return true;
  }catch(e){
    setStatus(e.message,'is-unsaved');
    if(!autosave)alert(e.message);
    return false;
  }finally{
    if(!autosave&&button)button.disabled=false;
  }
}

window.HDL_BUILDER_CONTEXT={
  getDocument:()=>doc,
  setDocument,
  getSelected:()=>selected,
  setSelected:(id)=>{selected=id;render()},
  find,render,snapshot,dirty,markSaved,uid,widget,section,add,insertSection,save,
  isDirty:()=>isDirty,
  getLastSavedAt:()=>lastSavedAt
};
document.querySelector('[data-action="save"]').onclick=()=>save({autosave:false});
window.addEventListener('keydown',e=>{
  if((e.ctrlKey||e.metaKey)&&e.key.toLowerCase()==='s'){e.preventDefault();save({autosave:false})}
});
const robotsSelect=document.getElementById('seo-robots');
robotsSelect?.addEventListener('change',()=>{syncPageMetaFromForm();dirty()});
if(doc.meta.seoRobots&&robotsSelect)robotsSelect.value=doc.meta.seoRobots;
render();updateUrl();
markSaved('Saved','is-saved');
})();

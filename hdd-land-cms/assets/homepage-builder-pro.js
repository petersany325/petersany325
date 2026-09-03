document.addEventListener('DOMContentLoaded',()=>{
  const form=document.querySelector('form.builder-controls input[name="entity"][value="page_builder"]')?.form;
  if(!form)return;

  const schemas={
    stats:[['icon','Icon class'],['value','Value'],['label','Label'],['url','Link URL'],['image','Image URL']],
    benefits:[['icon','Icon class'],['title','Title'],['subtitle','Subtitle'],['url','Link URL'],['image','Image URL']],
    solutions:[['icon','Icon class'],['title','Title'],['description','Description'],['button','Button text'],['url','Button URL'],['image','Image URL']],
    footer:[['heading','Column heading'],['links','Links (Link^URL; Link^URL)']]
  };
  const defaults={
    stats:['fa-solid fa-chart-line','100+','NEW STAT','',''],
    benefits:['fa-solid fa-star','NEW BENEFIT','Short description','',''],
    solutions:['fa-solid fa-layer-group','New solution','Describe this solution.','Learn more','#',''],
    footer:['New column','Link one^#; Link two^#']
  };
  const titles={stats:'Statistics',benefits:'Benefits',solutions:'Corporate divisions',footer:'Footer menu columns'};
  const clean=value=>String(value??'').replace(/[|\r\n]+/g,' ').trim();
  function escapeHtml(value){const node=document.createElement('span');node.textContent=value;return node.innerHTML}
  function parse(textarea,schema){return textarea.value.split(/\r?\n/).filter(line=>line.trim()).map(line=>{const values=line.split('|');while(values.length<schema.length)values.push('');return values.slice(0,schema.length)})}
  function parseFooterLinks(raw){
    return String(raw||'').split(';').map(part=>{
      const bits=part.split('^');
      return [clean(bits[0]||''),clean(bits[1]||'#')];
    }).filter(pair=>pair[0]!=='');
  }
  function serializeFooterLinks(links){
    return links.map(([label,url])=>clean(label)+'^'+clean(url||'#')).filter(item=>item!=='^'&&!item.startsWith('^')).join('; ');
  }

  function visualEditor(textarea){
    const type=textarea.dataset.visualList,schema=schemas[type];if(!schema)return;
    let rows=parse(textarea,schema),dragged=-1;
    const source=textarea.closest('.visual-source');source.classList.add('source-enhanced');
    const box=document.createElement('div');box.className='visual-list-editor wide';source.before(box);

    function sync(){textarea.value=rows.map(row=>row.map(clean).join('|')).join('\n');textarea.dispatchEvent(new Event('input',{bubbles:true}))}

    function mountFooterLinks(fields,rowIndex){
      const links=parseFooterLinks(rows[rowIndex][1]||'');
      const wrap=document.createElement('div');
      wrap.className='footer-links-editor field-wide';
      wrap.innerHTML='<div class="footer-links-head"><strong>Menu links</strong><button type="button" data-add-link><i class="fa-solid fa-plus"></i> Add link</button></div><div class="footer-links-list"></div>';
      const list=wrap.querySelector('.footer-links-list');

      function writeLinks(){
        rows[rowIndex][1]=serializeFooterLinks(links);
        const card=fields.closest('.visual-item');
        if(card)card.querySelector('header small').textContent=rows[rowIndex][1]||'No links yet';
        sync();
      }

      function renderLinks(){
        list.innerHTML='';
        if(!links.length)list.innerHTML='<p class="empty-visual compact">No links in this column yet.</p>';
        links.forEach((link,linkIndex)=>{
          const row=document.createElement('div');
          row.className='footer-link-row';
          row.innerHTML=
            '<input data-link-field="0" placeholder="Label" value="'+escapeHtml(link[0])+'">'+
            '<input data-link-field="1" placeholder="/page or https://" value="'+escapeHtml(link[1])+'">'+
            '<button type="button" data-link-up title="Move up"><i class="fa-solid fa-arrow-up"></i></button>'+
            '<button type="button" data-link-down title="Move down"><i class="fa-solid fa-arrow-down"></i></button>'+
            '<button type="button" data-link-del title="Delete link"><i class="fa-regular fa-trash-can"></i></button>';
          row.addEventListener('input',event=>{
            const input=event.target.closest('[data-link-field]');if(!input)return;
            links[linkIndex][Number(input.dataset.linkField)]=input.value;
            writeLinks();
          });
          row.querySelector('[data-link-up]').onclick=()=>{if(linkIndex===0)return;[links[linkIndex-1],links[linkIndex]]=[links[linkIndex],links[linkIndex-1]];writeLinks();renderLinks()};
          row.querySelector('[data-link-down]').onclick=()=>{if(linkIndex>=links.length-1)return;[links[linkIndex+1],links[linkIndex]]=[links[linkIndex],links[linkIndex+1]];writeLinks();renderLinks()};
          row.querySelector('[data-link-del]').onclick=()=>{links.splice(linkIndex,1);writeLinks();renderLinks()};
          list.appendChild(row);
        });
      }

      wrap.querySelector('[data-add-link]').onclick=()=>{links.push(['New link','#']);writeLinks();renderLinks()};
      fields.appendChild(wrap);
      renderLinks();
    }

    function render(){
      box.innerHTML=
        '<div class="visual-list-head">'+
          '<div><strong>'+titles[type]+'</strong><small>Online edit • duplicate • reorder • delete. Changes sync to the live preview.</small></div>'+
          '<div class="visual-list-actions">'+
            '<button type="button" data-collapse-all title="Collapse all"><i class="fa-solid fa-angles-up"></i></button>'+
            '<button type="button" data-expand-all title="Expand all"><i class="fa-solid fa-angles-down"></i></button>'+
            '<button type="button" data-source><i class="fa-solid fa-code"></i> Source</button>'+
            '<button type="button" class="add-item"><i class="fa-solid fa-plus"></i> Add '+(type==='footer'?'column':'item')+'</button>'+
          '</div>'+
        '</div><div class="visual-items"></div>';

      const list=box.querySelector('.visual-items');
      rows.forEach((row,index)=>{
        const card=document.createElement('article');
        card.className='visual-item collapsed';
        card.draggable=true;
        card.dataset.index=String(index);
        const title=row[type==='stats'?1:type==='footer'?0:1]||row[2]||'Untitled item';
        const subtitle=type==='footer'?row[1]:(row[2]||row[3]||'');

        card.innerHTML=
          '<header>'+
            '<span class="drag-grip" title="Drag to reorder"><i class="fa-solid fa-grip-vertical"></i></span>'+
            '<i class="'+(type==='footer'?'fa-solid fa-sitemap':clean(row[0]||'fa-regular fa-square'))+' item-symbol"></i>'+
            '<div class="visual-item-copy"><strong>'+escapeHtml(title)+'</strong><small>'+escapeHtml(subtitle||'Click Edit to change this item')+'</small></div>'+
            '<div class="visual-item-menu" role="toolbar" aria-label="Item actions">'+
              '<button type="button" data-move="up" title="Move up"><i class="fa-solid fa-arrow-up"></i></button>'+
              '<button type="button" data-move="down" title="Move down"><i class="fa-solid fa-arrow-down"></i></button>'+
              '<button type="button" data-duplicate title="Duplicate"><i class="fa-regular fa-copy"></i></button>'+
              '<button type="button" data-toggle title="Edit online"><i class="fa-solid fa-pen"></i></button>'+
              '<button type="button" data-delete title="Delete"><i class="fa-regular fa-trash-can"></i></button>'+
            '</div>'+
          '</header><div class="visual-fields"></div>';

        const fields=card.querySelector('.visual-fields');
        schema.forEach(([key,label],fieldIndex)=>{
          if(type==='footer'&&key==='links'){
            const headingWrap=document.createElement('label');
            headingWrap.className='';
            const headingInput=document.createElement('input');
            headingInput.value=row[0]||'';
            headingInput.dataset.field='0';
            headingInput.placeholder='Column heading';
            headingWrap.append(document.createTextNode('Column heading'),headingInput);
            fields.appendChild(headingWrap);
            mountFooterLinks(fields,index);
            return;
          }
          if(type==='footer'&&key==='heading')return;
          const wrapper=document.createElement('label');
          wrapper.className=(key==='description'||key==='links'?'field-wide ':'')+(key==='image'?'media-field':'')+(key==='icon'?'icon-field':'');
          const input=(key==='description'||key==='links')?document.createElement('textarea'):document.createElement('input');
          if(key==='description'||key==='links')input.rows=3;
          input.value=row[fieldIndex]||'';
          input.dataset.field=String(fieldIndex);
          input.placeholder=key==='url'?'https:// or /page':key==='image'?'Choose from library or paste URL':key==='links'?'Home^/; Forum^/forum; Contact^/contact.php':'';
          wrapper.append(document.createTextNode(label),input);
          if(key==='image'){
            const media=document.createElement('button');media.type='button';media.dataset.mediaBrowser='dynamic';media.innerHTML='<i class="fa-regular fa-images"></i> Library';
            const holder=document.createElement('div');holder.className='media-input';input.replaceWith(holder);holder.append(input,media);
          }
          if(key==='icon'){
            const pick=document.createElement('button');pick.type='button';pick.className='icon-pick-btn';pick.innerHTML='<i class="fa-solid fa-icons"></i> Icons';
            pick.onclick=()=>{
              const iconInput=wrapper.querySelector('input');
              const existing=document.querySelector('input[name="icon"]');
              if(existing&&window.HDL_ICON_CATALOG){
                existing.value=iconInput.value;
                existing.dispatchEvent(new Event('input',{bubbles:true}));
                const preview=existing.parentElement?.querySelector('.icon-preview');
                if(preview)preview.click();
                const apply=()=>{iconInput.value=existing.value;iconInput.dispatchEvent(new Event('input',{bubbles:true}))};
                const modal=document.querySelector('.icon-modal');
                if(modal){
                  const observer=new MutationObserver(()=>{if(modal.hidden){apply();observer.disconnect()}});
                  observer.observe(modal,{attributes:true,attributeFilter:['hidden']});
                }else apply();
              }else{
                const value=prompt('Font Awesome class',iconInput.value||'fa-solid fa-star');
                if(value!==null){iconInput.value=value;iconInput.dispatchEvent(new Event('input',{bubbles:true}))}
              }
            };
            const holder=document.createElement('div');holder.className='icon-field-row';input.replaceWith(holder);holder.append(input,pick);
          }
          fields.appendChild(wrapper);
        });

        card.addEventListener('input',event=>{
          const input=event.target.closest('[data-field]');if(!input)return;
          rows[index][Number(input.dataset.field)]=input.value;
          const nextTitle=rows[index][type==='stats'?1:type==='footer'?0:1]||rows[index][2]||'Untitled item';
          card.querySelector('header strong').textContent=nextTitle;
          card.querySelector('header small').textContent=type==='footer'?(rows[index][1]||''):(rows[index][2]||rows[index][3]||'Click Edit to change this item');
          if(Number(input.dataset.field)===0&&type!=='footer')card.querySelector('.item-symbol').className=clean(input.value||'fa-regular fa-square')+' item-symbol';
          sync();
        });

        card.querySelector('[data-toggle]').onclick=()=>card.classList.toggle('collapsed');
        card.querySelector('[data-delete]').onclick=()=>{if(confirm('Delete this item? This only removes it after you Save the homepage.')){rows.splice(index,1);sync();render()}};
        card.querySelector('[data-duplicate]').onclick=()=>{rows.splice(index+1,0,[...row]);sync();render()};
        card.querySelectorAll('[data-move]').forEach(button=>button.onclick=()=>{
          const next=button.dataset.move==='up'?index-1:index+1;if(next<0||next>=rows.length)return;
          [rows[index],rows[next]]=[rows[next],rows[index]];sync();render();
        });
        card.addEventListener('dragstart',()=>{dragged=index;card.classList.add('dragging')});
        card.addEventListener('dragend',()=>card.classList.remove('dragging'));
        card.addEventListener('dragover',event=>event.preventDefault());
        card.addEventListener('drop',event=>{
          event.preventDefault();if(dragged<0||dragged===index)return;
          const [moved]=rows.splice(dragged,1);rows.splice(index,0,moved);sync();render();
        });
        list.appendChild(card);
      });

      if(!rows.length)list.innerHTML='<p class="empty-visual">No items yet. Click <b>Add '+(type==='footer'?'column':'item')+'</b> to create one online.</p>';
      box.querySelector('.add-item').onclick=()=>{rows.push([...defaults[type]]);sync();render();const last=list.querySelector('.visual-item:last-child');if(last){last.classList.remove('collapsed');last.scrollIntoView({behavior:'smooth',block:'nearest'})}};
      box.querySelector('[data-source]').onclick=()=>source.classList.toggle('source-visible');
      box.querySelector('[data-collapse-all]').onclick=()=>list.querySelectorAll('.visual-item').forEach(item=>item.classList.add('collapsed'));
      box.querySelector('[data-expand-all]').onclick=()=>list.querySelectorAll('.visual-item').forEach(item=>item.classList.remove('collapsed'));
    }

    textarea.addEventListener('change',()=>{rows=parse(textarea,schema);render()});
    render();
  }

  form.querySelectorAll('[data-visual-list]').forEach(visualEditor);

  const workspace=document.querySelector('.builder-workspace');
  const sectionsRoot=form.querySelector('[data-builder-sections]');
  if(workspace&&sectionsRoot){
    const nav=document.createElement('nav');
    nav.className='homepage-section-nav';
    nav.setAttribute('aria-label','Homepage sections');
    workspace.prepend(nav);

    function sectionLabel(node){
      const summary=node.querySelector(':scope > summary');
      if(!summary)return node.dataset.pageSection||'Section';
      const clone=summary.cloneNode(true);
      clone.querySelectorAll('i,span').forEach(el=>el.remove());
      return clone.textContent.trim()||node.dataset.pageSection;
    }

    function enabledKey(id){
      return ({sediv_banner:'sediv_banner_enabled',stats:'stats_enabled',benefits:'benefits_enabled',solutions:'solutions_enabled',pricing:'pricing_enabled',footer:'footer_enabled'})[id]||'';
    }

    function refreshNav(){
      nav.innerHTML='<div class="homepage-section-nav-head"><span>ONLINE MENU</span><strong>Homepage sections</strong><small>Jump • On/Off • edit items in each panel</small></div><div class="homepage-section-nav-list"></div>';
      const list=nav.querySelector('.homepage-section-nav-list');
      const panels=[
        form.querySelector('[data-builder-panel="global"]'),
        ...sectionsRoot.querySelectorAll('[data-page-section]'),
        form.querySelector('[data-builder-panel="footer"]')
      ].filter(Boolean);

      panels.forEach(node=>{
        const id=node.dataset.pageSection||node.dataset.builderPanel||'section';
        const key=enabledKey(id);
        const checkbox=key?form.querySelector('[name="'+CSS.escape(key)+'"]'):null;
        const enabled=!checkbox||checkbox.checked;
        const btn=document.createElement('button');
        btn.type='button';
        btn.className='homepage-section-nav-item'+(enabled?'':' is-off');
        btn.innerHTML='<i class="fa-solid fa-'+(enabled?'eye':'eye-slash')+'"></i><span>'+escapeHtml(sectionLabel(node))+'</span>'+(checkbox?'<em data-toggle-enabled>'+(enabled?'On':'Off')+'</em>':'');
        btn.onclick=event=>{
          if(event.target.closest('[data-toggle-enabled]')&&checkbox){
            checkbox.checked=!checkbox.checked;
            checkbox.dispatchEvent(new Event('change',{bubbles:true}));
            refreshNav();
            return;
          }
          node.open=true;
          node.scrollIntoView({behavior:'smooth',block:'start'});
          node.classList.add('section-flash');
          setTimeout(()=>node.classList.remove('section-flash'),900);
        };
        list.appendChild(btn);
      });
    }

    refreshNav();
    form.addEventListener('change',event=>{
      if(event.target.matches('input[type="checkbox"][name$="_enabled"]'))refreshNav();
    });
    new MutationObserver(refreshNav).observe(sectionsRoot,{childList:true});
  }

  const iframe=document.querySelector('iframe[name="homepage-preview"]'),status=document.querySelector('[data-preview-status]');
  let timer=0,request=0;
  async function refreshPreview(){
    const current=++request;status.textContent='Updating…';status.classList.add('working');
    try{
      const response=await fetch('homepage-preview.php',{method:'POST',body:new FormData(form),headers:{Accept:'text/html'}});
      if(!response.ok)throw new Error('Preview failed');
      const html=await response.text();
      if(current!==request)return;
      iframe.srcdoc=html;status.textContent='Live';status.classList.remove('working');
    }catch(error){status.textContent='Preview unavailable';status.classList.remove('working')}
  }
  function schedule(){clearTimeout(timer);timer=setTimeout(refreshPreview,420)}
  form.addEventListener('input',schedule);form.addEventListener('change',schedule);
  form.addEventListener('reset',()=>setTimeout(schedule));
  form.addEventListener('paste',event=>{
    const el=event.target;
    if(!(el instanceof HTMLTextAreaElement)&&!(el instanceof HTMLInputElement))return;
    if(el.dataset.visualList)return;
    const cd=event.clipboardData;if(!cd)return;
    const html=cd.getData('text/html')||'';
    const plain=cd.getData('text/plain')||'';
    if(!html||!/<(p|div|br|li|h[1-6]|blockquote|u|span)\b/i.test(html))return;
    event.preventDefault();
    const box=document.createElement('div');
    box.innerHTML=html.replace(/<br\s*\/?>/gi,'\n').replace(/<\/(p|div|h[1-6]|li|blockquote|tr)>/gi,'\n');
    let text=box.innerText.replace(/\u00a0/g,' ');
    if(el instanceof HTMLInputElement)text=text.replace(/[\r\n]+/g,' ');
    const start=el.selectionStart??el.value.length,end=el.selectionEnd??start;
    el.value=el.value.slice(0,start)+text+el.value.slice(end);
    el.selectionStart=el.selectionEnd=start+text.length;
    el.dispatchEvent(new Event('input',{bubbles:true}));
  });
});

(() => {
    'use strict';
    const button=document.querySelector('.message-icon-button'),panel=document.getElementById('message-popover'),list=panel?.querySelector('[data-message-preview-list]');
    if(!button||!panel||!list)return;
    let loadedAt=0,loading=false;

    function updateBadge(total){
        let badge=button.querySelector('.notification-badge');
        if(total>0){if(!badge){badge=document.createElement('span');badge.className='notification-badge';button.appendChild(badge);}badge.textContent=total>99?'99+':String(total);}
        else badge?.remove();
    }

    function render(conversations){
        list.innerHTML='';
        if(!conversations.length){const empty=document.createElement('div');empty.className='message-popover-state';empty.textContent='Henüz bir konuşmanız yok.';list.appendChild(empty);return;}
        const fragment=document.createDocumentFragment();
        conversations.forEach(conversation=>{
            const item=document.createElement('a');item.className=`message-preview-item${conversation.unreadCount>0?' unread':''}`;item.href=conversation.url;
            const avatar=document.createElement('span');avatar.className='message-preview-avatar';avatar.textContent=conversation.initial;
            const copy=document.createElement('span');copy.className='message-preview-copy';const name=document.createElement('strong'),preview=document.createElement('span');name.textContent=conversation.username;preview.textContent=conversation.preview;copy.append(name,preview);
            const meta=document.createElement('span');meta.className='message-preview-meta';const time=document.createElement('time');time.textContent=conversation.time;meta.appendChild(time);if(conversation.unreadCount>0){const unread=document.createElement('b');unread.textContent=conversation.unreadCount>99?'99+':String(conversation.unreadCount);meta.appendChild(unread);}
            item.append(avatar,copy,meta);fragment.appendChild(item);
        });
        list.appendChild(fragment);
    }

    async function load(){
        if(loading||Date.now()-loadedAt<15000)return;loading=true;
        if(!loadedAt)list.innerHTML='<div class="message-popover-state">Konuşmalar yükleniyor…</div>';
        try{const response=await fetch(button.dataset.previewUrl,{cache:'no-store',headers:{'X-Requested-With':'XMLHttpRequest'}}),data=await response.json();if(!response.ok||!data.success)throw new Error();render(data.conversations||[]);updateBadge(Number(data.unreadCount)||0);loadedAt=Date.now();}
        catch(error){list.innerHTML='<div class="message-popover-state">Mesajlar şu anda yüklenemedi.</div>';}
        finally{loading=false;}
    }

    function close(){panel.hidden=true;button.setAttribute('aria-expanded','false');button.setAttribute('aria-label','Mesajları aç');}
    button.addEventListener('click',()=>{const opening=panel.hidden;if(opening){const notificationPanel=document.getElementById('notification-popover'),notificationButton=document.querySelector('[data-notification-button]');if(notificationPanel)notificationPanel.hidden=true;if(notificationButton){notificationButton.setAttribute('aria-expanded','false');notificationButton.setAttribute('aria-label','Bildirimleri aç');}panel.hidden=false;button.setAttribute('aria-expanded','true');button.setAttribute('aria-label','Mesajları kapat');load();}else close();});
    document.addEventListener('click',event=>{if(!panel.hidden&&!event.target.closest('.message-popover-wrap'))close();});
    document.addEventListener('keydown',event=>{if(event.key==='Escape'&&!panel.hidden){close();button.focus();}});
    document.addEventListener('project:messages-updated',()=>{loadedAt=0;if(!panel.hidden)load();});
})();

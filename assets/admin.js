(() => {
  'use strict';
  const box = document.getElementById('mtmi-box');
  if (!box) return;
  const id = document.getElementById('mtmi-id');
  const result = document.getElementById('mtmi-result');
  const buttons = box.querySelectorAll('button');
  const request = async (action) => {
    result.className = 'is-loading'; result.textContent = MTMI.working;
    buttons.forEach((b) => { b.disabled = true; });
    const body = new URLSearchParams({ action, nonce: document.getElementById('mtmi_nonce').value, imdb_id: id.value.trim(), post_id: box.dataset.postId });
    box.querySelectorAll('[data-option]').forEach((field) => body.append(`options[${field.dataset.option}]`, field.checked ? '1' : ''));
    try {
      const response = await fetch(MTMI.ajaxUrl, { method: 'POST', credentials: 'same-origin', headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' }, body });
      const json = await response.json();
      if (!json.success) throw new Error(json.data?.message || MTMI.error);
      if (action === 'mtmi_preview') {
        const d = json.data;
        result.className = 'is-success preview';
        result.replaceChildren();
        if (d.poster) { const img = document.createElement('img'); img.src=d.poster; img.alt=''; img.loading='lazy'; result.append(img); }
        const strong=document.createElement('strong'); strong.textContent=d.title || ''; result.append(strong);
        const meta=document.createElement('span'); meta.textContent=[d.year,d.genre,d.imdb_rating ? `IMDb ${d.imdb_rating}` : ''].filter(Boolean).join(' • '); result.append(meta);
      } else { result.className='is-success'; result.textContent=MTMI.done; }
    } catch (error) { result.className='is-error'; result.textContent=error.message || MTMI.error; }
    finally { buttons.forEach((b) => { b.disabled = false; }); }
  };
  document.getElementById('mtmi-preview').addEventListener('click', () => request('mtmi_preview'));
  document.getElementById('mtmi-import').addEventListener('click', () => request('mtmi_import'));
})();

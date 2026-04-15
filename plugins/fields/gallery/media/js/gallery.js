const SELECTORS = {
  root: '.plg-fields-gallery',
  fileInput: '.plg-fields-gallery__file-input',
  mediaBtn: '.plg-fields-gallery__media-btn',
  dropzone: '.plg-fields-gallery__dropzone',
  list: '.plg-fields-gallery__list',
  value: '.plg-fields-gallery__value',
};

class GalleryField {
  constructor(root) {
    this.root = root;
    this.fileInput = root.querySelector(SELECTORS.fileInput);
    this.mediaBtn = root.querySelector(SELECTORS.mediaBtn);
    this.dropzone = root.querySelector(SELECTORS.dropzone);
    this.list = root.querySelector(SELECTORS.list);
    this.valueInput = root.querySelector(SELECTORS.value);
    this.uploadUrl = root.dataset.uploadUrl;

    this.bindEvents();
    this.initSortable();
    this.syncValue();
  }

  bindEvents() {
    this.fileInput?.addEventListener('change', (event) => {
      this.uploadFiles([...event.target.files]);
      event.target.value = '';
    });

    this.dropzone?.addEventListener('dragover', (event) => {
      event.preventDefault();
      this.dropzone.classList.add('is-dragover');
    });

    this.dropzone?.addEventListener('dragleave', () => {
      this.dropzone.classList.remove('is-dragover');
    });

    this.dropzone?.addEventListener('drop', (event) => {
      event.preventDefault();
      this.dropzone.classList.remove('is-dragover');
      this.uploadFiles([...event.dataTransfer.files]);
    });

    this.list?.addEventListener('input', (event) => {
      if (
        event.target.classList.contains('plg-fields-gallery__title') ||
        event.target.classList.contains('plg-fields-gallery__alt')
      ) {
        this.syncValue();
      }
    });

    this.list?.addEventListener('click', (event) => {
      const removeButton = event.target.closest('.plg-fields-gallery__remove');
      if (!removeButton) {
        return;
      }

      event.preventDefault();
      removeButton.closest('.plg-fields-gallery__item')?.remove();
      this.syncValue();
    });

    this.mediaBtn?.addEventListener('click', () => {
      this.openMediaManager();
    });
  }

  initSortable() {
    if (!window.Sortable || !this.list) {
      return;
    }

    window.Sortable.create(this.list, {
      animation: 150,
      ghostClass: 'plg-fields-gallery__item--ghost',
      onEnd: () => this.syncValue(),
    });
  }

  async uploadFiles(files = []) {
    const imageFiles = files.filter((file) => file.type.startsWith('image/'));

    for (const file of imageFiles) {
      try {
        const path = await this.uploadSingleFile(file);
        this.addItem({ src: path, title: '', alt: '' });
      } catch (error) {
        // eslint-disable-next-line no-console
        console.error(error);
        Joomla.renderMessages({ error: [error.message || 'Upload failed'] });
      }
    }

    this.syncValue();
  }

  async uploadSingleFile(file) {
    const token = Joomla.getOptions('csrf.token');
    const formData = new FormData();

    formData.append('file', file);

    if (token) {
      formData.append(token, '1');
    }

    const response = await fetch(this.uploadUrl, {
      method: 'POST',
      body: formData,
      credentials: 'same-origin',
    });

    const result = await response.json();

    if (!response.ok || result.success !== true || !result.data?.path) {
      throw new Error(result.message || 'Server rejected the upload.');
    }

    return result.data.path;
  }

  addItem(image) {
    const item = document.createElement('article');
    item.className = 'plg-fields-gallery__item';
    item.dataset.src = image.src;

    item.innerHTML = `
      <button type="button" class="btn btn-danger btn-sm plg-fields-gallery__remove" aria-label="Remove image">×</button>
      <img class="plg-fields-gallery__thumb" src="${this.escapeAttr(image.src)}" alt="" loading="lazy">
      <div class="plg-fields-gallery__meta">
        <input type="text" class="form-control form-control-sm plg-fields-gallery__title" placeholder="Title" value="${this.escapeAttr(image.title || '')}">
        <input type="text" class="form-control form-control-sm plg-fields-gallery__alt" placeholder="Alt text" value="${this.escapeAttr(image.alt || '')}">
      </div>
    `;

    this.list?.appendChild(item);
  }

  openMediaManager() {
    if (!Joomla.MediaManager || typeof Joomla.MediaManager.open !== 'function') {
      Joomla.renderMessages({ error: ['Media Manager integration is unavailable.'] });
      return;
    }

    Joomla.MediaManager.open({
      types: ['image'],
      multiple: true,
      onSelect: (files) => {
        (files || []).forEach((file) => {
          const path = file.path || file.url || file.name;
          if (!path) {
            return;
          }

          this.addItem({ src: path, title: file.title || '', alt: file.alt || '' });
        });

        this.syncValue();
      },
    });
  }

  syncValue() {
    const items = [...this.list.querySelectorAll('.plg-fields-gallery__item')].map((item) => ({
      src: item.dataset.src || '',
      title: item.querySelector('.plg-fields-gallery__title')?.value || '',
      alt: item.querySelector('.plg-fields-gallery__alt')?.value || '',
    })).filter((item) => item.src);

    this.valueInput.value = JSON.stringify(items);
  }

  escapeAttr(value) {
    return String(value)
      .replaceAll('&', '&amp;')
      .replaceAll('"', '&quot;')
      .replaceAll('<', '&lt;')
      .replaceAll('>', '&gt;');
  }
}

document.addEventListener('DOMContentLoaded', () => {
  document.querySelectorAll(SELECTORS.root).forEach((root) => {
    if (!root.dataset.galleryReady) {
      root.dataset.galleryReady = '1';
      // eslint-disable-next-line no-new
      new GalleryField(root);
    }
  });
});

(function () {
  'use strict';
  var input = document.querySelector('[data-story-file]');
  var wrap = document.querySelector('[data-story-preview]');
  if (!input || !wrap) return;
  var image = wrap.querySelector('[data-story-preview-image]');
  var video = wrap.querySelector('[data-story-preview-video]');
  var empty = wrap.querySelector('[data-story-preview-empty]');
  var error = document.querySelector('[data-story-preview-error]');
  var currentUrl = '';
  var imageMax = parseInt(input.dataset.maxImageMb || '8', 10) * 1024 * 1024;
  var videoMax = parseInt(input.dataset.maxVideoMb || '20', 10) * 1024 * 1024;
  function clearPreview() {
    if (currentUrl) URL.revokeObjectURL(currentUrl);
    currentUrl = '';
    image.hidden = true; video.hidden = true; empty.hidden = false;
    image.removeAttribute('src'); video.pause(); video.removeAttribute('src'); video.load();
    error.hidden = true; error.textContent = '';
  }
  input.addEventListener('change', function () {
    clearPreview();
    var file = input.files && input.files[0];
    if (!file) { wrap.hidden = true; return; }
    var isImage = ['image/jpeg', 'image/png', 'image/webp'].indexOf(file.type) !== -1;
    var isVideo = ['video/mp4', 'video/webm'].indexOf(file.type) !== -1;
    var max = isImage ? imageMax : videoMax;
    if (!isImage && !isVideo) {
      error.textContent = 'Choose a JPG, PNG or WebP image, or an MP4/WebM video.';
      error.hidden = false; wrap.hidden = true; input.value = ''; return;
    }
    if (file.size > max) {
      error.textContent = 'This file is too large for the configured upload limit.';
      error.hidden = false; wrap.hidden = true; input.value = ''; return;
    }
    currentUrl = URL.createObjectURL(file);
    wrap.hidden = false; empty.hidden = true;
    if (isImage) { image.src = currentUrl; image.hidden = false; }
    else { video.src = currentUrl; video.hidden = false; video.load(); }
  });
  window.addEventListener('pagehide', function () { if (currentUrl) URL.revokeObjectURL(currentUrl); });
})();

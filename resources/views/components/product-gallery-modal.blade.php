{{-- Modal Tampil Penuh Galeri Foto Produk (Lightbox) --}}
<div id="productGalleryModal" class="gallery-lightbox-overlay" style="display:none;" role="dialog" aria-modal="true" aria-label="Galeri Foto Penuh">
  <div class="gallery-lightbox-backdrop" onclick="closeProductGallery()"></div>

  <div class="gallery-lightbox-container">
    {{-- Top Bar --}}
    <div class="gallery-lightbox-topbar">
      <div class="gallery-topbar-info">
        <h4 class="gallery-lightbox-title" id="galleryProductTitle">Galeri Foto Produk</h4>
        <div class="gallery-lightbox-counter" id="galleryCounter">1 / 1</div>
      </div>
      <button type="button" class="gallery-lightbox-close" onclick="closeProductGallery()" aria-label="Tutup galeri">
        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
          <line x1="18" y1="6" x2="6" y2="18"></line>
          <line x1="6" y1="6" x2="18" y2="18"></line>
        </svg>
      </button>
    </div>

    {{-- Main Stage --}}
    <div class="gallery-lightbox-stage">
      <button type="button" class="gallery-nav-btn gallery-prev" id="galleryPrevBtn" onclick="prevGalleryImage()" aria-label="Foto sebelumnya">
        <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
          <polyline points="15 18 9 12 15 6"></polyline>
        </svg>
      </button>

      <div class="gallery-image-viewport">
        <img id="galleryActiveImage" src="" alt="Foto Penuh" class="gallery-active-img">
      </div>

      <button type="button" class="gallery-nav-btn gallery-next" id="galleryNextBtn" onclick="nextGalleryImage()" aria-label="Foto selanjutnya">
        <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
          <polyline points="9 18 15 12 9 6"></polyline>
        </svg>
      </button>
    </div>

    {{-- Bottom Thumbnails Strip --}}
    <div class="gallery-lightbox-thumbnails" id="galleryThumbsContainer"></div>
  </div>
</div>

<script>
(function() {
  let galleryImages = [];
  let currentGalleryIndex = 0;
  let touchStartX = 0;
  let touchEndX = 0;

  window.openProductGallery = function(images, startIndex, title) {
    if (!images || images.length === 0) return;
    galleryImages = images;
    currentGalleryIndex = Math.max(0, Math.min(images.length - 1, startIndex || 0));

    const modal = document.getElementById('productGalleryModal');
    if (!modal) return;

    if (title) {
      document.getElementById('galleryProductTitle').textContent = title;
    }

    renderGalleryThumbs();
    updateGalleryView();

    modal.style.display = 'flex';
    document.body.style.overflow = 'hidden';
  };

  window.closeProductGallery = function() {
    const modal = document.getElementById('productGalleryModal');
    if (!modal) return;
    modal.style.display = 'none';
    document.body.style.overflow = '';
  };

  window.setGalleryImage = function(index) {
    if (index < 0 || index >= galleryImages.length) return;
    currentGalleryIndex = index;
    updateGalleryView();
  };

  window.nextGalleryImage = function() {
    if (galleryImages.length <= 1) return;
    currentGalleryIndex = (currentGalleryIndex + 1) % galleryImages.length;
    updateGalleryView();
  };

  window.prevGalleryImage = function() {
    if (galleryImages.length <= 1) return;
    currentGalleryIndex = (currentGalleryIndex - 1 + galleryImages.length) % galleryImages.length;
    updateGalleryView();
  };

  function updateGalleryView() {
    const activeImg = document.getElementById('galleryActiveImage');
    const counter = document.getElementById('galleryCounter');
    const prevBtn = document.getElementById('galleryPrevBtn');
    const nextBtn = document.getElementById('galleryNextBtn');

    if (activeImg) {
      activeImg.src = galleryImages[currentGalleryIndex];
    }

    if (counter) {
      counter.textContent = (currentGalleryIndex + 1) + ' / ' + galleryImages.length;
    }

    // Nav buttons visibility if only 1 image
    const isMultiple = galleryImages.length > 1;
    if (prevBtn) prevBtn.style.display = isMultiple ? 'flex' : 'none';
    if (nextBtn) nextBtn.style.display = isMultiple ? 'flex' : 'none';

    // Highlight active thumb
    const thumbs = document.querySelectorAll('.gallery-thumb-item');
    thumbs.forEach(function(thumb, idx) {
      if (idx === currentGalleryIndex) {
        thumb.classList.add('active');
        thumb.scrollIntoView({ behavior: 'smooth', block: 'nearest', inline: 'center' });
      } else {
        thumb.classList.remove('active');
      }
    });
  }

  function renderGalleryThumbs() {
    const container = document.getElementById('galleryThumbsContainer');
    if (!container) return;

    if (galleryImages.length <= 1) {
      container.style.display = 'none';
      return;
    }

    container.style.display = 'flex';
    let html = '';
    galleryImages.forEach(function(url, idx) {
      html += `
        <button type="button" class="gallery-thumb-item ${idx === currentGalleryIndex ? 'active' : ''}" onclick="setGalleryImage(${idx})" aria-label="Lihat foto ke-${idx + 1}">
          <img src="${url}" alt="Thumbnail ${idx + 1}">
        </button>
      `;
    });
    container.innerHTML = html;
  }

  // Keyboard navigation
  document.addEventListener('keydown', function(e) {
    const modal = document.getElementById('productGalleryModal');
    if (!modal || modal.style.display === 'none') return;

    if (e.key === 'Escape') {
      closeProductGallery();
    } else if (e.key === 'ArrowRight') {
      nextGalleryImage();
    } else if (e.key === 'ArrowLeft') {
      prevGalleryImage();
    }
  });

  // Touch Swipe for mobile devices
  const stage = document.querySelector('.gallery-lightbox-stage');
  if (stage) {
    stage.addEventListener('touchstart', function(e) {
      touchStartX = e.changedTouches[0].screenX;
    }, { passive: true });

    stage.addEventListener('touchend', function(e) {
      touchEndX = e.changedTouches[0].screenX;
      handleSwipe();
    }, { passive: true });
  }

  function handleSwipe() {
    const swipeDistance = touchEndX - touchStartX;
    if (Math.abs(swipeDistance) > 40) {
      if (swipeDistance < 0) {
        nextGalleryImage(); // Swipe left -> Next
      } else {
        prevGalleryImage(); // Swipe right -> Prev
      }
    }
  }
})();
</script>


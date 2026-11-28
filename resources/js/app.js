/* Import fonts */
import '@fontsource/poppins/400.css';
import '@fontsource/poppins/500.css';
import '@fontsource/poppins/600.css';
import '@fontsource/poppins/700.css';

import Alpine from 'alpinejs';

window.Alpine = Alpine;

Alpine.start();

// ========== INTERAKSI TAHAP B ==========

// 1) MUNCUL SAAT SCROLL (Reveal on Scroll)
document.addEventListener('DOMContentLoaded', function () {
  const prefersReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

  if (!prefersReducedMotion) {
    // Tambahkan kelas reveal-ready ke document
    document.documentElement.classList.add('reveal-ready');

    // Setup IntersectionObserver
    const observer = new IntersectionObserver(
      (entries) => {
        entries.forEach((entry) => {
          if (entry.isIntersecting) {
            entry.target.classList.add('is-visible');
            observer.unobserve(entry.target); // Hanya sekali
          }
        });
      },
      { threshold: 0.12 }
    );

    // Observasi semua elemen [data-reveal]
    document.querySelectorAll('[data-reveal]').forEach((el) => {
      // Set transition-delay dari atribut data-reveal-delay
      const delay = el.getAttribute('data-reveal-delay');
      if (delay) {
        const delayMs = parseInt(delay, 10) * 50; // 50ms per angka
        el.style.transitionDelay = delayMs + 'ms';
      }
      observer.observe(el);
    });
  }
});

// 2) ANGKA NAIK (Count To Animation)
document.addEventListener('DOMContentLoaded', function () {
  const prefersReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

  if (!prefersReducedMotion) {
    const observer = new IntersectionObserver(
      (entries) => {
        entries.forEach((entry) => {
          if (entry.isIntersecting && !entry.target.dataset.counted) {
            const target = parseInt(entry.target.getAttribute('data-count-to'), 10);
            const duration = 800; // ms
            const start = Date.now();

            entry.target.dataset.counted = 'true'; // Cegah animasi berlipat

            const animate = () => {
              const elapsed = Date.now() - start;
              const progress = Math.min(elapsed / duration, 1);

              // EaseOut: 1 - (1 - progress)^3
              const eased = 1 - Math.pow(1 - progress, 3);
              const current = Math.round(target * eased);

              entry.target.textContent = current.toLocaleString('id-ID');

              if (progress < 1) {
                requestAnimationFrame(animate);
              } else {
                entry.target.textContent = target.toLocaleString('id-ID');
              }
            };

            requestAnimationFrame(animate);
          }
        });
      },
      { threshold: 0.12 }
    );

    // Observasi semua elemen [data-count-to]
    document.querySelectorAll('[data-count-to]').forEach((el) => {
      observer.observe(el);
    });
  }
});

// 3) UMPAN BALIK AKSI (Form Submission & Loading State)
document.addEventListener('DOMContentLoaded', function () {
  // Status memproses: disable submit button pada form submit
  document.querySelectorAll('form:not([data-no-loading])').forEach((form) => {
    form.addEventListener('submit', function (e) {
      const submitButton = form.querySelector('button[type="submit"]');
      if (submitButton) {
        const loadingText = submitButton.getAttribute('data-loading-text') || 'Memproses...';
        const originalText = submitButton.textContent;

        submitButton.disabled = true;
        submitButton.setAttribute('aria-busy', 'true');
        submitButton.textContent = loadingText;

        // Kembalikan keadaan awal pada pageshow (bfcache)
        const handlePageShow = () => {
          submitButton.disabled = false;
          submitButton.setAttribute('aria-busy', 'false');
          submitButton.textContent = originalText;
          window.removeEventListener('pageshow', handlePageShow);
        };
        window.addEventListener('pageshow', handlePageShow);
      }
    });
  });
});

// 4) PENCARIAN KATALOG LANGSUNG (Live Search)
document.addEventListener('DOMContentLoaded', function () {
  const form = document.querySelector('form[data-katalog-search]');
  if (!form) return;

  const resultsContainer = document.getElementById('hasil-katalog');
  if (!resultsContainer) return;

  let abortController = null;
  let searchTimeout = null;

  const performSearch = () => {
    // Batalkan fetch sebelumnya
    if (abortController) {
      abortController.abort();
    }
    abortController = new AbortController();

    // Ambil data form
    const formData = new FormData(form);
    const params = new URLSearchParams(formData);

    // Cek minimal 2 karakter untuk input q atau kosong
    const q = formData.get('q') || '';
    if (q.length > 0 && q.length < 2) {
      return;
    }

    // Tampilkan status loading
    const statusEl = form.querySelector('[data-search-status]') || (() => {
      const el = document.createElement('div');
      el.setAttribute('aria-live', 'polite');
      el.style.fontSize = '0.875rem';
      el.style.color = 'var(--color-muted)';
      el.style.marginTop = '0.5rem';
      form.appendChild(el);
      return el;
    })();
    statusEl.textContent = 'Memuat...';

    // Fetch ke URL yang sama dengan query string
    const url = form.action + '?' + params.toString();
    fetch(url, {
      headers: {
        'X-Requested-With': 'XMLHttpRequest',
      },
      signal: abortController.signal,
    })
      .then((response) => {
        if (!response.ok) throw new Error('Network response not ok');
        return response.text();
      })
      .then((html) => {
        // Parse respons HTML dan cari #hasil-katalog
        const parser = new DOMParser();
        const doc = parser.parseFromString(html, 'text/html');
        const newResults = doc.getElementById('hasil-katalog');

        if (newResults) {
          resultsContainer.innerHTML = newResults.innerHTML;
        }

        // Perbarui URL lewat history.replaceState
        window.history.replaceState({}, '', url);

        // Hapus status loading
        statusEl.textContent = '';
      })
      .catch((error) => {
        if (error.name !== 'AbortError') {
          statusEl.textContent = 'Gagal memuat hasil.';
          console.error('Search error:', error);
        }
      });
  };

  // Debounce 350ms untuk input q
  form.querySelectorAll('input, select').forEach((field) => {
    field.addEventListener('input', () => {
      clearTimeout(searchTimeout);
      searchTimeout = setTimeout(performSearch, 350);
    });
    field.addEventListener('change', () => {
      clearTimeout(searchTimeout);
      searchTimeout = setTimeout(performSearch, 350);
    });
  });
});

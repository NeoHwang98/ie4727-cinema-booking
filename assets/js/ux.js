document.addEventListener('DOMContentLoaded', () => {
  document.querySelectorAll('.scroll-arrow').forEach(btn => {
    btn.addEventListener('click', () => {
      const dir = parseInt(btn.getAttribute('data-scroll'), 10) || 1;
      const targetSel = btn.getAttribute('data-target');
      const container = document.querySelector(targetSel);
      if (!container) return;
      // If container is set to cycle mode, switch active panel index without slider behavior
      if (container.dataset.mode === 'cycle') {
        const panels = Array.from(container.querySelectorAll('.panel'));
        if (panels.length === 0) return;
        let idx = parseInt(container.dataset.index || '0', 10);
        idx = (idx + dir + panels.length) % panels.length;
        panels.forEach((p,i)=> p.classList.toggle('active', i === idx));
        container.dataset.index = String(idx);
      } else {
        // fallback scroll (not used for Highlights now)
        const delta = (container.querySelector('.panel, .tile-lg')?.getBoundingClientRect().width || 300) * dir;
        container.scrollBy({ left: delta + 16, behavior: 'smooth' });
      }
    });
  });
});

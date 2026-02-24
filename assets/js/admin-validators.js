document.addEventListener('DOMContentLoaded', () => {
  const sanitizeInt = (el) => {
    el.value = el.value.replace(/[^0-9]/g, '');
    if (el.value.startsWith('0')) el.value = el.value.replace(/^0+/, '');
    if (el.value === '') el.value = '';
  };
  const sanitizeMoney = (el) => {
    let v = el.value.replace(/[^0-9.]/g, '');
    const firstDot = v.indexOf('.');
    if (firstDot !== -1) {
      v = v.slice(0, firstDot + 1) + v.slice(firstDot + 1).replace(/\./g, '');
      const parts = v.split('.');
      if (parts[1] !== undefined) parts[1] = parts[1].slice(0, 2);
      v = parts.join('.');
    }
    if (v.startsWith('.')) v = '0' + v;
    el.value = v;
  };

  document.querySelectorAll('input.int-pos').forEach(el => {
    el.setAttribute('inputmode','numeric');
    el.addEventListener('keydown', (e) => {
      if (["e","E","+","-"," "].includes(e.key)) e.preventDefault();
    });
    el.addEventListener('input', () => sanitizeInt(el));
  });

  document.querySelectorAll('input.money-pos').forEach(el => {
    el.setAttribute('inputmode','decimal');
    el.addEventListener('keydown', (e) => {
      if (["e","E","+","-"," "].includes(e.key)) { e.preventDefault(); return; }
      if (["Backspace","Delete","ArrowLeft","ArrowRight","Tab","Home","End"].includes(e.key)) return;
      if (e.key === '.') { if (el.value.includes('.')) e.preventDefault(); return; }
      if (!/^[0-9]$/.test(e.key)) e.preventDefault();
    });
    el.addEventListener('input', () => sanitizeMoney(el));
    el.addEventListener('blur', () => { if (el.value !== '') { const n = parseFloat(el.value); if (!isNaN(n) && n >= 0) el.value = n.toFixed(2); }});
  });
});


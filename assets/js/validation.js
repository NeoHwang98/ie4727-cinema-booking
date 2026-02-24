document.addEventListener('DOMContentLoaded', () => {
  const nameExp = /^[A-Za-z]+(?:\s[A-Za-z]+)*$/;
  const symbolExp = /[^A-Za-z\s]/;
  const doubleSpaceExp = /\s{2,}/;

  const ensureError = (el) => {
    let msg = el.parentElement.querySelector('small.error');
    if (!msg) {
      msg = document.createElement('small');
      msg.className = 'error';
      el.parentElement.appendChild(msg);
    }
    return msg;
  };
  const show = (el, message) => {
    const out = ensureError(el);
    el.classList.add('invalid');
    out.textContent = message;
  };
  const hide = (el) => {
    const out = ensureError(el);
    el.classList.remove('invalid');
    out.textContent = '';
  };

  // Validators
  const validateName = (el) => {
    if (!el) return true;
    const val = (el.value || '').trim();
    const errors = [];
    if (!val) errors.push('Name cannot be empty.');
    if (symbolExp.test(val)) errors.push('No symbols or numbers allowed.');
    if (doubleSpaceExp.test(val)) errors.push('No double spaces allowed.');
    if (!nameExp.test(val) && errors.length === 0) errors.push('Use only letters and single spaces.');
    if (errors.length) { show(el, errors.join(' ')); return false; }
    hide(el); return true;
  };

  const validateEmail = (el) => {
    if (!el) return true;
    const val = (el.value || '').trim();
    if (!val) { show(el, 'Email cannot be empty. Example: name@mail.com'); return false; }
    const parts = val.split('@');
    if (parts.length !== 2) { show(el, "Email must include a single '@' (e.g., user@mail.com)"); return false; }
    const [username, domain] = parts;
    if (!username) return show(el, "Text before '@' is missing (e.g., user@mail.com)"), false;
    if (username.startsWith('.') || username.endsWith('.')) return show(el, 'Username cannot start/end with a dot'), false;
    if (username.startsWith('-') || username.endsWith('-')) return show(el, 'Username cannot start/end with a hyphen'), false;
    if (username.includes('..')) return show(el, 'No consecutive dots in username'), false;
    if (!/^[A-Za-z0-9._-]+$/.test(username)) return show(el, 'Invalid character in username'), false;
    if (!domain) return show(el, 'Domain is missing (e.g., @mail.com)'), false;
    if (domain.includes('..')) return show(el, 'No consecutive dots in domain'), false;
    const domainParts = domain.split('.');
    if (domainParts.some(p => p.trim()==='')) return show(el, 'Incomplete domain parts'), false;
    if (domainParts.length < 2 || domainParts.length > 4) return show(el, 'Domain should have 2–4 parts'), false;
    for (const part of domainParts) { if (!/^[A-Za-z0-9._-]+$/.test(part)) return show(el, 'Invalid domain character'), false; }
    const tld = domainParts[domainParts.length-1];
    if (tld.length < 2 || tld.length > 3) return show(el, 'TLD must be 2–3 letters'), false;
    if (!/^[A-Za-z]+$/.test(tld)) return show(el, 'TLD must be letters only'), false;
    hide(el); return true;
  };

  const validatePhoneLocal = (el) => {
    if (!el) return true;
    const ok = /^0[0-9]{8,9}$/.test((el.value||'').trim());
    if (!ok) { show(el, 'Phone must start with 0 and be 9–10 digits'); return false; }
    hide(el); return true;
  };

  const validatePassword = (el) => {
    if (!el) return true;
    const ok = /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[^A-Za-z0-9]).{8,}$/.test(el.value||'');
    if (!ok) { show(el, 'Min 8, include upper, lower, number, special'); return false; }
    hide(el); return true;
  };

  const validateCard = (el) => { if (!el) return true; const ok=/^[0-9]{16}$/.test((el.value||'').replace(/\s+/g,'')); if(!ok){show(el,'16-digit card number');return false;} hide(el); return true; };
  const validateCardName = (el)=> validateName(el);
  const validateExpiry = (el) => { if(!el)return true; const ok=/^(0[1-9]|1[0-2])\/[0-9]{2}$/.test((el.value||'').trim()); if(!ok){show(el,'Expiry MM/YY');return false;} hide(el); return true; };
  const validateCvv = (el)=>{ if(!el)return true; const ok=/^[0-9]{3}$/.test((el.value||'').trim()); if(!ok){show(el,'3-digit CVV');return false;} hide(el); return true; };

  const wire = (el, fn) => { if (!el) return; el.addEventListener('input', () => fn(el)); };
  const guardSubmit = (form, validators) => {
    if (!form) return;
    form.addEventListener('submit', (e) => {
      const results = validators.map(fn => fn());
      if (results.some(r => r === false)) e.preventDefault();
    });
  };

  // Login form
  const loginForm = document.querySelector('form.auth-form');
  if (loginForm && loginForm.querySelector('input[name=password]') && !loginForm.querySelector('input[name=name]')) {
    const email = loginForm.querySelector('input[name=email]');
    const password = loginForm.querySelector('input[name=password]');
    wire(email, validateEmail); wire(password, validatePassword);
    guardSubmit(loginForm, [() => validateEmail(email), () => validatePassword(password)]);
  }

  // Signup form
  const signupForm = document.querySelector('form.auth-form');
  if (signupForm && signupForm.querySelector('input[name=name]')) {
    const name = signupForm.querySelector('input[name=name]');
    const email = signupForm.querySelector('input[name=email]');
    const password = signupForm.querySelector('input[name=password]');
    wire(name, validateName); wire(email, validateEmail); wire(password, validatePassword);
    guardSubmit(signupForm, [() => validateName(name), () => validateEmail(email), () => validatePassword(password)]);
  }

  // Checkout form
  const checkout = document.getElementById('checkout-form');
  if (checkout) {
    const name = checkout.querySelector('input[name=name]');
    const email = checkout.querySelector('input[name=email]');
    const phone = checkout.querySelector('input[name=phone_sg]');
    const card = checkout.querySelector('input[name=card]');
    const cardName = checkout.querySelector('input[name=card_name]');
    const expiry = checkout.querySelector('input[name=expiry_month]');
    const cvv = checkout.querySelector('input[name=cvv]');
    const validatePhoneSG = (el)=>{ if(!el)return true; const v=(el.value||'').trim(); const ok=/^[89][0-9]{7}$/.test(v); if(!ok){show(el,'Phone must start with 8 or 9 and be 8 digits.');return false;} hide(el); return true; };
    // Forbid non-digits while typing and cap to 8
    if (phone) {
      phone.addEventListener('keydown', (e) => {
        const allowed = ['Backspace','Delete','ArrowLeft','ArrowRight','Tab','Home','End'];
        if (allowed.includes(e.key)) return;
        if (!/^[0-9]$/.test(e.key)) e.preventDefault();
      });
      phone.addEventListener('input', () => { phone.value = (phone.value||'').replace(/[^0-9]/g,'').slice(0,8); });
    }
    const validateExpiryMonth = (el)=>{ if(!el)return true; const ok=/^\d{4}-\d{2}$/.test((el.value||'').trim()); if(!ok){show(el,'Select expiry month');return false;} hide(el); return true; };
    wire(name, validateName); wire(email, validateEmail); wire(phone, validatePhoneSG);
    wire(card, validateCard); wire(cardName, validateCardName); wire(expiry, validateExpiryMonth); wire(cvv, validateCvv);
    guardSubmit(checkout, [() => validateName(name), () => validateEmail(email), () => validatePhoneSG(phone), () => validateCard(card), () => validateCardName(cardName), () => validateExpiryMonth(expiry), () => validateCvv(cvv)]);
  }

  // Forgot password form
  const forgot = document.querySelector('form[action$="forgot.php"], form[action$="/forgot.php"]');
  if (forgot) {
    const email = forgot.querySelector('input[name=email]');
    wire(email, validateEmail);
    guardSubmit(forgot, [() => validateEmail(email)]);
  }
});

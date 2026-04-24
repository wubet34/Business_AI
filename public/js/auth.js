// ── Auth page logic ────────────────────────────────────────

function initAuth() {
  const loginForm    = document.getElementById('login-form');
  const registerForm = document.getElementById('register-form');
  const toRegister   = document.getElementById('to-register');
  const toLogin      = document.getElementById('to-login');
  const loginCard    = document.getElementById('login-card');
  const registerCard = document.getElementById('register-card');

  toRegister?.addEventListener('click', (e) => {
    e.preventDefault();
    loginCard.style.display    = 'none';
    registerCard.style.display = 'block';
  });
  toLogin?.addEventListener('click', (e) => {
    e.preventDefault();
    registerCard.style.display = 'none';
    loginCard.style.display    = 'block';
  });

  loginForm?.addEventListener('submit', async (e) => {
    e.preventDefault();
    const btn   = loginForm.querySelector('button[type=submit]');
    const email = loginForm.email.value.trim();
    const pass  = loginForm.password.value;
    if (!email || !pass) return toast('Please fill all fields.', 'warning');
    setLoading(btn, true);
    try {
      const res = await Auth.login({ email, password: pass });
      saveUser(res.user);
      window.location.href = 'index.html';
    } catch (err) {
      toast(err.message || 'Login failed.', 'error');
    } finally {
      setLoading(btn, false);
    }
  });

  registerForm?.addEventListener('submit', async (e) => {
    e.preventDefault();
    const btn  = registerForm.querySelector('button[type=submit]');
    const name = registerForm.reg_name.value.trim();
    const email= registerForm.reg_email.value.trim();
    const pass = registerForm.reg_password.value;
    const role = registerForm.reg_role.value;
    if (!name || !email || !pass) return toast('Please fill all fields.', 'warning');
    if (pass.length < 6) return toast('Password must be at least 6 characters.', 'warning');
    setLoading(btn, true);
    try {
      await Auth.register({ name, email, password: pass, role });
      toast('Registered successfully! Please login.', 'success');
      registerCard.style.display = 'none';
      loginCard.style.display    = 'block';
      registerForm.reset();
    } catch (err) {
      toast(err.message || 'Registration failed.', 'error');
    } finally {
      setLoading(btn, false);
    }
  });
}

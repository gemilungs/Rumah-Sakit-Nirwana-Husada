    // TAB SWITCH
    const tabLogin = document.getElementById("tab-login");
    const tabRegister = document.getElementById("tab-register");
    const loginSection = document.getElementById("loginSection");
    const registerSection = document.getElementById("registerSection");

    function switchTo(tab) {
      const isLogin = tab === "login";
      tabLogin.classList.toggle("tab--active", isLogin);
      tabLogin.classList.toggle("tab--muted", !isLogin);

      tabRegister.classList.toggle("tab--active", !isLogin);
      tabRegister.classList.toggle("tab--muted", isLogin);

      loginSection.style.display = isLogin ? "block" : "none";
      registerSection.style.display = isLogin ? "none" : "block";
    }

    if (tabLogin && tabRegister) {
      tabLogin.onclick = () => switchTo("login");
      tabRegister.onclick = () => switchTo("register");
    }
    const gotoRegisterEl = document.getElementById("gotoRegister");
    if (gotoRegisterEl) {
      // Debug information to help trace why clicks don't navigate
      try {
        console.debug('[DEBUG] gotoRegister exists', gotoRegisterEl);
        const cs = window.getComputedStyle(gotoRegisterEl);
        console.debug('[DEBUG] gotoRegister styles', { pointerEvents: cs.pointerEvents, visibility: cs.visibility, display: cs.display, opacity: cs.opacity });
        console.debug('[DEBUG] gotoRegister rect', gotoRegisterEl.getBoundingClientRect());

        // Capture-phase click logger to verify click reaches this element
        gotoRegisterEl.addEventListener('click', function (ev) {
          console.debug('[DEBUG] gotoRegister clicked (capture)', ev);
        }, true);
      } catch (err) {
        console.error('[DEBUG] error while probing gotoRegister', err);
      }

      gotoRegisterEl.onclick = e => {
      // Only intercept if the page has an in-page register section (like login-register.php)
      if (registerSection && loginSection) {
        e.preventDefault();
        switchTo("register");
      }
      // otherwise allow the link to navigate to register.php
    };
    }

    // If there's a "Login" link on a standalone register page, allow it to switch tabs when both sections exist
    const gotoLoginEl = document.getElementById("gotoLogin");
    if (gotoLoginEl) {
      // When both in-page sections exist, switch tabs instead of navigating
      gotoLoginEl.onclick = e => {
        if (registerSection && loginSection) {
          e.preventDefault();
          switchTo("login");
        }
      };

      // Fallback: when this is a standalone page (no in-page sections), ensure link navigates even if other handlers interfere
      gotoLoginEl.addEventListener('click', function (ev) {
        if (!(registerSection && loginSection)) {
          ev.stopImmediatePropagation();
          ev.preventDefault();
          const href = this.getAttribute('href') || 'login.php';
          window.location.href = href;
        }
      }, true); // use capture phase to run before other handlers
    }

    // Fallback for the "Daftar" link as well
    if (gotoRegisterEl) {
      gotoRegisterEl.addEventListener('click', function (ev) {
        if (!(registerSection && loginSection)) {
          ev.stopImmediatePropagation();
          ev.preventDefault();
          const href = this.getAttribute('href') || 'register.php';
          window.location.href = href;
        }
      }, true);
    }

    // SHOW/HIDE PASSWORD (Login)
    const toggleLoginPwd = document.getElementById("toggleLoginPwd");
    const loginPwd = document.getElementById("loginPassword");
    function handleToggleLoginPwd(ev){
      try { if (ev && ev.preventDefault) ev.preventDefault(); } catch(e){}
      const btn = document.getElementById('toggleLoginPwd');
      const pwd = document.getElementById('loginPassword');
      if(!btn || !pwd) return;
      // is password currently visible?
      const isVisible = pwd.type === 'text';
      // toggle
      pwd.type = isVisible ? 'password' : 'text';
      // update UI -- show action available (when hidden -> show; when visible -> hide)
      btn.textContent = isVisible ? 'Show' : 'Hide';
      btn.classList.toggle('active', !isVisible);
      btn.setAttribute('aria-pressed', String(!isVisible));
      btn.setAttribute('aria-label', isVisible ? 'Tampilkan password' : 'Sembunyikan password');
      // small tap animation
      try { btn.animate([{ transform: 'scale(.98)' }, { transform: 'scale(1)' }], { duration: 120 }); } catch (err) {}
    }

    // helper to initialize state (keeps UI consistent on load)
    function initToggleLoginPwd(){
      const btn = document.getElementById('toggleLoginPwd');
      const pwd = document.getElementById('loginPassword');
      if(!btn || !pwd) return;
      const isVisible = pwd.type === 'text';
      btn.textContent = isVisible ? 'Hide' : 'Show';
      btn.setAttribute('aria-pressed', String(isVisible));
      btn.setAttribute('aria-label', isVisible ? 'Sembunyikan password' : 'Tampilkan password');
      btn.classList.toggle('active', isVisible);
    }

    // Primary binding when elements exist
    if (toggleLoginPwd && loginPwd) {
      toggleLoginPwd.addEventListener('click', handleToggleLoginPwd);
      // support pointer/touch as well
      toggleLoginPwd.addEventListener('pointerdown', handleToggleLoginPwd);
      toggleLoginPwd.addEventListener('touchstart', handleToggleLoginPwd);
      toggleLoginPwd.addEventListener('keydown', function(ev){ if(ev.key === ' ' || ev.key === 'Enter'){ ev.preventDefault(); handleToggleLoginPwd(ev); }});
      initToggleLoginPwd();
    } else {
      // Delegation fallback (works if element added later or if something blocks direct handlers)
      document.body.addEventListener('click', function(e){
        const btn = e.target.closest && e.target.closest('#toggleLoginPwd');
        if(btn){ handleToggleLoginPwd(e); }
      });
      document.body.addEventListener('pointerdown', function(e){
        const btn = e.target.closest && e.target.closest('#toggleLoginPwd');
        if(btn){ handleToggleLoginPwd(e); }
      });
      document.body.addEventListener('keydown', function(e){
        if((e.key === ' ' || e.key === 'Enter') && document.activeElement && document.activeElement.id === 'toggleLoginPwd'){
          e.preventDefault(); handleToggleLoginPwd(e);
        }
      });
      // try init in case elements are added later
      setTimeout(initToggleLoginPwd, 250);
    }

    // Password strength feature disabled — removed non-functional UI
    // (Keeping a simple textual hint in the registration form instead.)

    // Set max DOB
    const dob = document.getElementById('dob');
    if (dob) {
      const today = new Date().toISOString().split('T')[0];
      dob.max = today;
    }

    // LOGIN FORM SUBMISSION - client-side validation only, server handles submissions
    document.getElementById("loginForm")?.addEventListener("submit", async (e) => {
      const loginId = document.getElementById("loginId").value;
      const loginPassword = document.getElementById("loginPassword").value;
      if (!loginId || !loginPassword) {
        e.preventDefault();
        alert("Email/Username dan password wajib diisi.");
        return;
      }
      // If the form action points to login-action.php (server), attempt AJAX via api/auth.php
      const form = e.target;
      if (form.getAttribute('action') === 'login-action.php') {
        e.preventDefault();
        try {
          const res = await fetch('api/auth.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'login', username: loginId, password: loginPassword })
          });
          const data = await res.json();
          if (data.success) {
            // redirect according to role
            if (data.data && data.data.redirect) {
              window.location.href = data.data.redirect;
            } else {
              window.location.reload();
            }
          } else {
            alert('Login gagal: ' + data.message);
          }
        } catch (err) {
          console.error(err);
          // fallback to normal submit
          form.submit();
        }
      }
    });

    // REGISTER FORM SUBMISSION - client-side validation only, server handles submissions
    document.getElementById("registerForm")?.addEventListener("submit", async (e) => {
      const pw = document.getElementById("regPassword").value;
      const pwc = document.getElementById("regConfirm").value;
      const gender = document.querySelector('input[name="gender"]:checked')?.value;
      const fullName = document.getElementById("fullName").value;
      const regEmail = document.getElementById("regEmail").value;
      const dob = document.getElementById("dob").value;
      const address = document.getElementById("address").value;

      if (pw !== pwc) {
        e.preventDefault();
        alert("Password dan konfirmasi tidak sama.");
        return;
      }
      if (!gender) {
        e.preventDefault();
        alert("Pilih jenis kelamin.");
        return;
      }

      // intercept form and use AJAX to api/auth.php
      const form = e.target;
      if (form.getAttribute('action') === 'register-action.php') {
        e.preventDefault();
        // generate username
        const username = fullName.toLowerCase().replace(/\s+/g, '') + Math.floor(Math.random() * 1000);
        try {
          const res = await fetch('api/auth.php', {
            method: 'POST',
            credentials: 'same-origin',
            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
            body: JSON.stringify({
              action: 'register',
              username: username,
              email: regEmail,
              password: pw,
              nama_lengkap: fullName,
              alamat: address,
              tanggal_lahir: dob,
              jenis_kelamin: gender === 'L' ? 'L' : 'P'
            })
          });
          if (!res.ok) {
            const txt = await res.text();
            alert('Registrasi gagal: server error ' + res.status + '\n' + txt);
            return;
          }
          const data = await res.json();
          if (data.success) {
            // Auto-login after register via login API
            const loginRes = await fetch('api/auth.php', {
              method: 'POST',
              credentials: 'same-origin',
              headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
              body: JSON.stringify({ action: 'login', username: username, password: pw })
            });
            if (!loginRes.ok) {
              console.warn('Login request returned non-OK', loginRes.status);
              alert('Registrasi sukses, tapi otomatis login gagal (server error). Silakan login secara manual.');
              window.location.href = 'login.php';
              return;
            }
            const loginData = await loginRes.json();
            if (loginData.success) {
              if (loginData.data && loginData.data.redirect) window.location.href = loginData.data.redirect;
              else window.location.href = 'index.php';
            } else {
              alert('Registrasi sukses, tapi login otomatis gagal: ' + loginData.message);
              window.location.href = 'login.php';
            }
          } else {
            alert('Registrasi gagal: ' + data.message);
          }
        } catch (err) {
          console.error(err);
          // fallback to normal submission
          form.submit();
        }
      }
    });
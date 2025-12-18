    (function(){
      const saveBtn = document.getElementById('saveBtn');
      const cancelBtn = document.getElementById('cancelBtn');
      const filterSelect = document.getElementById('filter');
      const apptsList = document.getElementById('apptsList');
      const toast = document.getElementById('toast');

      const togglePwd = document.getElementById('togglePwd');
      const pwdInput = document.getElementById('password');

      function showToast(text = 'Tersimpan', type = 'success') {
        if (!toast) return;
        toast.textContent = text;
        toast.style.display = 'block';
        // set color based on type
        if (type === 'success') { toast.style.backgroundColor = '#198754'; toast.style.color = '#fff'; }
        else if (type === 'error') { toast.style.backgroundColor = '#dc3545'; toast.style.color = '#fff'; }
        else { toast.style.backgroundColor = '#0d6efd'; toast.style.color = '#fff'; }
        setTimeout(() => { toast.style.display = 'none'; }, 3000);
      }

      // Show server-confirmation alert modal similar to the provided design
      function showServerNotice(text = '') {
        try {
          // use existing showModal helper: title matches screenshot 'localhost says'
          showModal('localhost says', `<div style="min-width:260px;">${text}</div>`);

          // style the modal to match dark background and green OK button
          const container = document.getElementById('profileModal');
          if (!container) return;
          const content = container.querySelector('.modal-content');
          if (content) {
            content.style.backgroundColor = '#0f1713';
            content.style.color = '#d7f7e6';
            content.style.borderRadius = '10px';
            content.style.boxShadow = '0 8px 24px rgba(0,0,0,0.6)';
            content.style.border = '1px solid rgba(0,0,0,0.3)';
          }
          const footerBtn = container.querySelector('.modal-footer button');
          if (footerBtn) {
            footerBtn.textContent = 'OK';
            footerBtn.className = 'btn';
            footerBtn.style.backgroundColor = '#c4f7d1';
            footerBtn.style.color = '#062612';
            footerBtn.style.borderRadius = '20px';
            footerBtn.style.border = 'none';
            footerBtn.style.padding = '8px 18px';
            footerBtn.addEventListener('click', () => { try { const m = bootstrap.Modal.getInstance(container); if (m) m.hide(); } catch(e){} });
          }

          // remove close icon to match screenshot
          const closeBtn = container.querySelector('.btn-close');
          if (closeBtn) closeBtn.remove();
        } catch (e) { console.error('showServerNotice error', e); }
      }


      async function saveProfile() {
        const name = document.getElementById('name').value.trim();
        const phone = document.getElementById('phone').value.trim();
        const email = document.getElementById('email').value.trim();
        const address = document.getElementById('address').value.trim();

        // basic validation
        if (email && !/^\S+@\S+\.\S+$/.test(email)) {
          showToast('Format email tidak valid', 'error');
          return;
        }
        if (phone && !/^[0-9+()\-\s]+$/.test(phone)) {
          showToast('Nomor telepon tidak valid', 'error');
          return;
        }

        // disable button while saving
        saveBtn.disabled = true;
        const originalText = saveBtn.innerHTML;
        saveBtn.innerHTML = 'Menyimpan...';

        try {
          console.debug('[PROFILE] saving', { name, phone, email, address });
          // Use POST with action=update for broader compatibility with servers that may not handle PUT payloads
          const payload = Object.assign({ action: 'update' }, {
            nama_lengkap: name || null,
            no_telepon: phone || null,
            email: email || null,
            alamat: address || null
          });
          console.debug('[PROFILE] sending payload', payload);
          const response = await fetch('api/user.php', {
            method: 'POST',
            credentials: 'same-origin',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload)
          });

          const text = await response.text();
          let data = null;
          try { data = JSON.parse(text); } catch(e) { data = null; }

          if (!response.ok) {
            console.error('[PROFILE] save failed', response.status, text);
            // If server refuses PUT (405), try POST fallback
            if (response.status === 405) {
              try {
                const res2 = await fetch('api/user.php', {
                  method: 'POST',
                  headers: { 'Content-Type': 'application/json' },
                  body: JSON.stringify(Object.assign({ action: 'update' }, { nama_lengkap: name || null, no_telepon: phone || null, email: email || null, alamat: address || null }))
                });
                const data2Text = await res2.text();
                let data2 = null; try { data2 = JSON.parse(data2Text); } catch(e) { data2 = null; }
                if (res2.ok && data2 && data2.success) {
                  showToast('Perubahan disimpan (POST fallback)');
                  if (data2.data) {
                    document.getElementById('name').value = data2.data.nama_lengkap || '';
                    document.getElementById('phone').value = data2.data.no_telepon || '';
                    document.getElementById('address').value = data2.data.alamat || '';
                    document.getElementById('email').value = data2.data.email || '';
                    const headerName = document.querySelector('.menu-user .menu-item span');
                    if (headerName) headerName.textContent = data2.data.nama_lengkap || data2.data.username || '';
                    const topName = document.getElementById('topbarName'); if (topName) topName.textContent = data2.data.nama_lengkap || data2.data.username || '';
                    const topEmail = document.getElementById('topbarEmail'); if (topEmail) topEmail.textContent = data2.data.email || '';
                    const leftTitle = document.getElementById('profile-title-left'); if (leftTitle) leftTitle.textContent = data2.data.nama_lengkap || data2.data.username || '';
                  }
                  return;
                } else {
                  showToast('Gagal simpan: ' + (data2 && data2.message ? data2.message : res2.statusText || 'Unknown error'), 'error');
                  return;
                }
              } catch (err) {
                console.error('[PROFILE] POST fallback error', err);
                showToast('Gagal simpan (fallback): ' + err.message);
                return;
              }
            }

            const msg = data && data.message ? data.message : (response.statusText || text || 'Server error');
            showToast('Gagal simpan: ' + msg, 'error');
            return;
          }

          if (data && data.success) {
            console.info('update response', data);
            const affected = data.data && typeof data.data.affected !== 'undefined' ? Number(data.data.affected) : 0;

            // Determine whether DB values actually differ from our payload
            const normalize = v => (v === null || typeof v === 'undefined') ? '' : String(v).trim();
            let db = data.data || {};
            const matches = (normalize(db.nama_lengkap) === normalize(payload.nama_lengkap)) &&
                            (normalize(db.no_telepon) === normalize(payload.no_telepon)) &&
                            (normalize(db.email) === normalize(payload.email)) &&
                            (normalize(db.alamat) === normalize(payload.alamat));

            const serverMsg = data.message || 'Perubahan disimpan';

            // Show modal if DB changed OR server explicitly says it's successful OR payload differs from DB
            if (affected > 0 || /berhasil/i.test(serverMsg) || !matches) {
              showServerNotice(serverMsg);
              console.debug('[PROFILE] notifying user: affected=', affected, 'matches=', matches, 'serverMsg=', serverMsg);
            } else {
              // No modal on strict 'no-change' where DB already matches payload
              console.debug('[PROFILE] no notification: affected=', affected, 'matches=', matches, 'serverMsg=', serverMsg);
            }

            // update UI and header name if present
            if (data.data) {
              document.getElementById('name').value = data.data.nama_lengkap || '';
              document.getElementById('phone').value = data.data.no_telepon || '';
              document.getElementById('address').value = data.data.alamat || '';
              document.getElementById('email').value = data.data.email || '';

              const headerName = document.querySelector('.menu-user .menu-item span');
              if (headerName) headerName.textContent = data.data.nama_lengkap || data.data.username || '';
              const topName = document.getElementById('topbarName'); if (topName) topName.textContent = data.data.nama_lengkap || data.data.username || '';
              const topEmail = document.getElementById('topbarEmail'); if (topEmail) topEmail.textContent = data.data.email || '';
              const leftTitle = document.getElementById('profile-title-left'); if (leftTitle) leftTitle.textContent = data.data.nama_lengkap || data.data.username || '';
            } else {
              loadProfile();
            }
          } else {
            const msg = data && data.message ? data.message : (response.statusText || 'Unknown error');
            showToast('Gagal simpan: ' + msg);
          }
        } catch (error) {
          console.error('saveProfile error', error);
          showToast('Gagal simpan data', 'error');
        } finally {
          saveBtn.disabled = false;
          saveBtn.innerHTML = originalText;
        }
      }

      async function loadProfile() {
        try {
          const res = await fetch('api/user.php', { credentials: 'same-origin' });
          if (res.status === 401) { window.location.href = 'login.php?next=' + encodeURIComponent(window.location.pathname); return; }
          const data = await res.json().catch(() => null);
          if (data && data.success && data.data) {
            document.getElementById('name').value = data.data.nama_lengkap || '';
            document.getElementById('phone').value = data.data.no_telepon || '';
            document.getElementById('address').value = data.data.alamat || '';
            document.getElementById('email').value = data.data.email || '';
            // update topbar and left title
            const topName = document.getElementById('topbarName'); if (topName) topName.textContent = data.data.nama_lengkap || data.data.username || '';
            const topEmail = document.getElementById('topbarEmail'); if (topEmail) topEmail.textContent = data.data.email || '';
            const leftTitle = document.getElementById('profile-title-left'); if (leftTitle) leftTitle.textContent = data.data.nama_lengkap || data.data.username || '';
          }
        } catch (err) { console.error('loadProfile error', err); }
      }

      function cancelEdit() {
        loadProfile();
        pwdInput.value = '';
        showToast('Perubahan dibatalkan');
      }

      function filterAppts() {
        const v = filterSelect.value;
        const items = apptsList.querySelectorAll('.appt');
        items.forEach(item => {
          const status = item.getAttribute('data-status') || 'all';
          item.style.display = (v === 'all' || v === status) ? '' : 'none';
        });
      }

      // Load history via API as well to keep client in sync (not required if server renders)
      // Client-side pagination state
      let limit = 6, offset = 0, totalCount = 0, activeStatusFilter = null;

      async function updateCountsAndSummary(counts, total) {
        totalCount = total;
        const s = `Total: ${total} — Pending: ${counts.pending || 0}, Dikonfirmasi: ${counts.dikonfirmasi || 0}, Selesai: ${counts.selesai || 0}, Dibatalkan: ${counts.dibatalkan || 0}`;
        document.getElementById('countsSummary').innerText = s;
      }

      async function loadHistory(initial = false) {
        try {
          if (initial) { offset = 0; apptsList.innerHTML = ''; }
          const params = new URLSearchParams({ limit: limit, offset: offset });
          if (activeStatusFilter) params.set('status', activeStatusFilter);
          const res = await fetch('api/history.php?' + params.toString());
          const data = await res.json();
          if (data.success && data.data && Array.isArray(data.data.bookings)) {
            // Append bookings
            data.data.bookings.forEach(b => {
              const status_raw = b.status || 'pending';
              let status_class = 'upcoming';
              let status_label = 'Menunggu';
              if (status_raw === 'selesai') { status_class = 'completed'; status_label = 'Selesai'; }
              else if (status_raw === 'dibatalkan') { status_class = 'canceled'; status_label = 'Dibatalkan'; }
              else if (status_raw === 'dikonfirmasi') { status_class = 'upcoming'; status_label = 'Dikonfirmasi'; }

              const date = new Date(b.tanggal_booking);
              const dateStr = date.toLocaleDateString('id-ID', { day:'2-digit', month:'short', year:'numeric'});

              const el = document.createElement('div');
              el.className = 'appt';
              el.setAttribute('data-status', status_class);
              el.innerHTML = `
                <div class="left">
                  <div class="date-pill">${dateStr}</div>
                  <div class="meta">
                    <div class="doc">${b.nama_dokter}</div>
                    <div class="poli">${b.spesialisasi || ''}</div>
                  </div>
                </div>
                <div>
                  <div class="status ${status_class}">${status_label}</div>
                  ${b.payment_status === 'belum_bayar' && b.status !== 'dibatalkan' ? '<div class="small text-danger mt-1">Belum bayar</div>' : ''}
                  <div style="margin-top:8px;text-align:center">
                    ${b.payment_status === 'belum_bayar' && b.status !== 'dibatalkan' ? `<a class="btn small btn-transparent" href="payment.php?booking_id=${b.id}">Bayar Sekarang</a>` : ''}
                    ${b.status === 'pending' ? `<button class="btn small btn-danger btn-cancel" data-id="${b.id}">Batalkan</button>` : ''}
                  </div>
                </div>
              `;
              apptsList.appendChild(el);
            });

            // Update counts and load more visibility
            await updateCountsAndSummary(data.data.counts, data.data.total);
            offset += data.data.bookings.length;
            const loadMoreBtn = document.getElementById('loadMoreBtn');
            if (offset < totalCount) { loadMoreBtn.style.display = 'inline-block'; } else { loadMoreBtn.style.display = 'none'; }

            // Attach handlers for cancel buttons
            document.querySelectorAll('.btn-cancel').forEach(btn => btn.addEventListener('click', cancelBooking));
          }
        } catch (err) {
          // ignore
        }
      }

      async function showDetail(e) {
        const id = e.target.getAttribute('data-id');
        try {
          const res = await fetch('api/booking.php?id=' + encodeURIComponent(id));
          const data = await res.json();
          if (data.success && data.data) {
            const b = data.data;
            const dateStr = new Date(b.tanggal_booking).toLocaleDateString('id-ID', { day:'2-digit', month:'short', year:'numeric'});
            const html = `
              <p><strong>${b.nama_pasien}</strong> — ${b.tipe_pasien}</p>
              <p><strong>Dokter:</strong> ${b.nama_dokter} (${b.spesialisasi})</p>
              <p><strong>Tanggal:</strong> ${dateStr}</p>
              <p><strong>Status:</strong> ${b.status}</p>
              <p><strong>Nomor antrian:</strong> ${b.nomor_antrian || '-'}</p>
              <p><strong>Keluhan:</strong> ${b.keluhan || '-'}</p>
              <p><strong>Catatan:</strong> ${b.catatan || '-'}</p>
            `;
            showModal('Detail Booking', html);
          }
        } catch (err) {}
      }

      async function cancelBooking(e) {
        if (!confirm('Batalkan booking ini?')) return;
        const id = e.target.getAttribute('data-id');
        try {
          const res = await fetch('api/booking.php', {
            method: 'PUT',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id: id, action: 'batal', alasan_batal: 'Dibatalkan oleh pasien' })
          });
          const data = await res.json();
          if (data.success) {
            // reload
            offset = 0; loadHistory(true);
            showToast('Booking dibatalkan');
          } else {
            showToast('Gagal batalkan: ' + data.message);
          }
        } catch (err) { showToast('Gagal batalkan booking'); }
      }

      function showModal(title, body) {
        let container = document.getElementById('profileModal');
        if (!container) {
          container = document.createElement('div');
          container.id = 'profileModal';
          container.className = 'modal fade';
          container.tabIndex = -1;
          container.innerHTML = `
            <div class="modal-dialog modal-dialog-centered">
              <div class="modal-content">
                <div class="modal-header">
                  <h5 class="modal-title">${title}</h5>
                  <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">${body}</div>
                <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button></div>
              </div>
            </div>
          `;
          document.body.appendChild(container);
        } else {
          container.querySelector('.modal-title').innerHTML = title;
          container.querySelector('.modal-body').innerHTML = body;
        }
        const modal = new bootstrap.Modal(container);
        modal.show();
      }

      // Attach load more if present
      const loadMoreBtn = document.getElementById('loadMoreBtn');
      if (loadMoreBtn) {
        loadMoreBtn.addEventListener('click', () => loadHistory());
      } else {
        console.debug('[PROFILE] loadMoreBtn not present');
      }

      // filter hook (guarded)
      if (filterSelect) {
        filterSelect.addEventListener('change', (ev) => {
          const v = ev.target.value;
          activeStatusFilter = null;
          if (v === 'upcoming') activeStatusFilter = 'pending|dikonfirmasi'; // include pending (not yet paid) as upcoming
          if (v === 'completed') activeStatusFilter = 'selesai';
          if (v === 'canceled') activeStatusFilter = 'dibatalkan';
          if (v === 'unpaid') activeStatusFilter = 'unpaid'; // custom filter: payment_status = belum_bayar
          offset = 0; loadHistory(true);
        });
      } else {
        console.debug('[PROFILE] filter select not found');
      }

      // initial load
      loadHistory(true);

      // ===== Auto-refresh history so user doesn't need to F5 =====
      let __historyPollInterval = null;
      let __historyLoading = false;
      function startHistoryPolling(){
        try{
          if(__historyPollInterval) clearInterval(__historyPollInterval);
          __historyPollInterval = setInterval(async function(){
            if(document.hidden) return; // skip when tab not visible
            if(__historyLoading) return; // avoid overlapping
            try{ __historyLoading = true; await loadHistory(true); } catch(e){} finally{ __historyLoading = false; }
          }, 15000);
        }catch(e){ console.warn('startHistoryPolling failed', e); }
      }
      function stopHistoryPolling(){ try{ if(__historyPollInterval) clearInterval(__historyPollInterval); __historyPollInterval = null; }catch(e){} }

      // start polling after initial load
      startHistoryPolling();

      // stop polling when page hidden/unloaded
      document.addEventListener('visibilitychange', function(){ if(document.hidden) stopHistoryPolling(); else startHistoryPolling(); });
      window.addEventListener('beforeunload', stopHistoryPolling);

      // toggle show/hide password (guarded)
      if (togglePwd && pwdInput) {
        togglePwd.addEventListener('click', () => {
          const showing = pwdInput.type === 'text';
          if (showing) {
            pwdInput.type = 'password';
            togglePwd.textContent = 'Tampilkan';
            togglePwd.setAttribute('aria-pressed', 'false');
            togglePwd.setAttribute('aria-label', 'Tampilkan kata sandi');
          } else {
            pwdInput.type = 'text';
            togglePwd.textContent = 'Sembunyikan';
            togglePwd.setAttribute('aria-pressed', 'true');
            togglePwd.setAttribute('aria-label', 'Sembunyikan kata sandi');
          }
        });
      } else {
        console.debug('[PROFILE] togglePwd or pwdInput not found');
      }

      // Attach handlers with safety and diagnostics
      try {
        if (saveBtn) {
          // expose for manual invocation and debugging
          window.saveProfile = saveProfile;
          try { saveBtn.addEventListener('click', (e) => { e.preventDefault(); console.debug('[PROFILE] saveBtn clicked'); saveProfile(); }); } catch (err) { console.error('addEventListener saveBtn failed', err); }
        } else {
          console.warn('[PROFILE] saveBtn not found');
          showToast('Tombol simpan tidak ditemukan', 'error');
        }

        if (cancelBtn) cancelBtn.addEventListener('click', cancelEdit);

        // Delegate as a fallback (handles dynamically replaced buttons)
        document.body.addEventListener('click', function(ev) {
          const b = ev.target.closest && ev.target.closest('#saveBtn');
          if (b) {
            ev.preventDefault(); console.debug('[PROFILE] delegated save click'); saveProfile();
          }
        });

        // keyboard support: Ctrl+S to save on profile page
        document.addEventListener('keydown', function(ev) {
          if ((ev.ctrlKey || ev.metaKey) && ev.key.toLowerCase() === 's') {
            ev.preventDefault(); console.debug('[PROFILE] Ctrl+S save'); saveProfile();
          }
        });
      } catch (e) { console.error('Error attaching handlers', e); }

      loadProfile();
      filterSelect.addEventListener('change', filterAppts);
    })();

    
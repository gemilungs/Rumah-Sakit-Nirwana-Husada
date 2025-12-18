// Admin Dashboard - Complete Functionality
(function() {
    const API_URL = 'api/';
    const views = ['dashboard', 'dokter', 'jadwal', 'pengaturan'];

    // ===== PAGE NAVIGATION & VIEW SWITCHING =====
    function showView(v) {
        // hide all known views
        views.forEach(vw => {
            const el = document.getElementById('view-' + vw);
            if (el) el.style.display = (vw === v) ? 'block' : 'none';
        });

        // update sidebar active class
        document.querySelectorAll('.sidebar .nav-link').forEach(n => {
            n.classList.toggle('active', n.dataset.view === v);
        });

        // update title and subtitle
        const activeLink = document.querySelector('.sidebar .nav-link[data-view="' + v + '"]');
        if (activeLink) {
            const title = activeLink.innerText.trim();
            document.getElementById('pageTitle').innerText = title;
            document.getElementById('pageSub').innerText = (v === 'dashboard') ? 'Aktivitas & statistik' : 'Kelola data';
        }

        // start/stop queue polling depending on view
        if (v === 'dashboard') startQueuePolling(); else stopQueuePolling();

        // Load data jika view dokter atau jadwal
        if (v === 'dokter') {
            loadDokter();
        } else if (v === 'jadwal') {
            // when switching to jadwal view we need both jadwal list and dokter list (for add/edit forms)
            loadDokter();
            loadJadwal();
        } else if (v === 'dashboard') {
            // refresh dashboard stats whenever user navigates back
            try { loadDashboardStats(); loadQueueStatus(); loadPendingPayments(); } catch(e) { console.warn('[ADMIN] loadDashboardStats failed on view change', e); }
        } else if (v === 'pengaturan') {
            // load admin profile into settings form
            try { loadAdminSettings(); } catch(e) { console.warn('[ADMIN] loadAdminSettings failed on view change', e); }
        }
    }

    // ===== ADMIN SETTINGS =====
    async function loadAdminSettings(){
        try{
            const res = await fetch('api/user.php', { credentials: 'same-origin' });
            if (res.status === 401) { window.location.href = 'login.php'; return; }
            const json = await res.json();
            if (!json.success) { console.warn('Gagal ambil profil admin', json); return; }
            const user = json.data;
            document.getElementById('settingName').value = user.nama_lengkap || '';
            document.getElementById('settingEmail').value = user.email || '';
        } catch (err) {
            console.error('[ADMIN] loadAdminSettings error', err);
        }
    }

    async function handleSaveProfile(e){
        e.preventDefault && e.preventDefault();
        const name = document.getElementById('settingName').value.trim();
        const email = document.getElementById('settingEmail').value.trim();
        if(!name){ alert('Nama wajib diisi'); return; }
        if(!email || !/^[^@\s]+@[^@\s]+\.[^@\s]+$/.test(email)){ alert('Email tidak valid'); return; }
        try{
            const btn = document.querySelector('#view-pengaturan .btn.btn-primary');
            if(btn) btn.disabled = true;
            const res = await fetch('api/user.php', {
                method: 'PUT',
                credentials: 'same-origin',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({ nama_lengkap: name, email: email })
            });
            const json = await res.json();
            if(json.success){
                alert('Profil berhasil disimpan');
                loadAdminSettings();
                // update topbar immediately
                try{
                    const topName = document.getElementById('topbarName');
                    const topEmail = document.getElementById('topbarEmail');
                    if(topName && json.data && json.data.nama_lengkap) topName.textContent = json.data.nama_lengkap;
                    if(topEmail && json.data && json.data.email) topEmail.textContent = json.data.email;
                }catch(e){ console.warn('Unable to update topbar after save', e); }
            }
            else alert('Gagal menyimpan profil: ' + (json.message || 'error'));
            if(btn) btn.disabled = false;
        }catch(err){ console.error('save profile error', err); alert('Terjadi kesalahan saat menyimpan profil'); }
    }

    async function handleChangePassword(e){
        e.preventDefault && e.preventDefault();
        const oldP = document.getElementById('oldPass').value;
        const newP = document.getElementById('newPass').value;
        const conf = document.getElementById('confirmPass').value;
        if(!oldP){ alert('Password lama wajib diisi'); return; }
        if(!newP || newP.length < 6){ alert('Password baru minimal 6 karakter'); return; }
        if(newP !== conf){ alert('Password baru & konfirmasi tidak cocok'); return; }
        try{
            const btn = document.querySelector('#view-pengaturan .btn.btn-danger');
            if(btn) btn.disabled = true;
            const res = await fetch('api/change-password.php', {
                method: 'POST',
                credentials: 'same-origin',
                headers: {'Content-Type':'application/json'},
                body: JSON.stringify({ password_lama: oldP, password_baru: newP })
            });
            const json = await res.json();
            if(json.success){ alert('Password berhasil diubah'); document.getElementById('oldPass').value=''; document.getElementById('newPass').value=''; document.getElementById('confirmPass').value=''; }
            else alert('Gagal mengganti password: ' + (json.message || 'error'));
            if(btn) btn.disabled = false;
        }catch(err){ console.error('change password error', err); alert('Terjadi kesalahan saat mengganti password'); }
    }

    // Initialize on page load
    document.addEventListener('DOMContentLoaded', function() {
        // Attach navigation handlers
        document.querySelectorAll('.sidebar .nav-link').forEach(a => {
            a.addEventListener('click', function(evt) {
                evt.preventDefault();
                const v = this.dataset.view;
                if (v) showView(v);
            });
        });

        // Setup all event listeners
        setupEventListeners();

        // Ensure doctor list is loaded when Add Schedule modal opens
        const modalAddScheduleEl = document.getElementById('modalAddSchedule');
        if (modalAddScheduleEl) {
            modalAddScheduleEl.addEventListener('show.bs.modal', async function (event) {
                const select = document.getElementById('inputDoctorSchedule');
                if (select) {
                    // Set temporary loading placeholder
                    select.innerHTML = '<option value="">-- Memuat daftar dokter... --</option>';
                    select.disabled = true;
                }
                try {
                    console.debug('[ADMIN] Opening AddSchedule modal - loading dokter (modal event)');
                    await loadDokter();
                    const selectAfter = document.getElementById('inputDoctorSchedule');
                    if (selectAfter) {
                        console.debug('[ADMIN] After loadDokter, options count=', selectAfter.options.length);
                        const note = document.getElementById('inputDoctorNote');
                        if (selectAfter.options.length <= 1) {
                            selectAfter.innerHTML = '<option value="">-- Tidak ada dokter tersedia --</option>';
                            selectAfter.disabled = true;
                            if (note) { note.style.display = 'block'; }
                        } else {
                            selectAfter.disabled = false;
                            if (note) { note.style.display = 'none'; }
                        }
                    }
                } catch (err) {
                    console.error('[ADMIN] Failed to load dokter when opening modal', err);
                    if (select) {
                        select.innerHTML = '<option value="">-- Gagal memuat dokter --</option>';
                        select.disabled = true;
                    }
                }
            });
        }

        // Also intercept the Add Schedule button click to ensure doctors are loaded before modal shows
        const addScheduleBtn = document.querySelector('[data-bs-target="#modalAddSchedule"]');
        if (addScheduleBtn) {
            addScheduleBtn.addEventListener('click', async function (e) {
                // Prevent Bootstrap's automatic show until we load the doctors
                e.preventDefault();
                e.stopImmediatePropagation();
                try {
                    const select = document.getElementById('inputDoctorSchedule');
                    if (select) {
                        select.innerHTML = '<option value="">-- Memuat daftar dokter... --</option>';
                        select.disabled = true;
                    }
                    console.debug('[ADMIN] AddSchedule button clicked - preloading doctors');
                    await loadDokter();
                    // show modal programmatically (use getOrCreateInstance for safe reuse)
                    const modalEl = document.getElementById('modalAddSchedule');
                    const modal = bootstrap.Modal.getOrCreateInstance(modalEl);
                    modal.show();
                } catch (err) {
                    console.error('[ADMIN] Error preloading doctors on AddSchedule click', err);
                    // still show modal but indicate failure
                    const modalEl = document.getElementById('modalAddSchedule');
                    const modal = bootstrap.Modal.getOrCreateInstance(modalEl);
                    modal.show();
                }
            });

            // Cleanup stuck overlays when modal is hidden and ensure close button forces hide
            const modalEl = document.getElementById('modalAddSchedule');
            if (modalEl) {
                modalEl.addEventListener('hidden.bs.modal', function () {
                    // Remove any leftover backdrops and body class that may remain
                    document.querySelectorAll('.modal-backdrop').forEach(el => el.remove());
                    document.body.classList.remove('modal-open');
                });

                // Force hide if close button is clicked and Bootstrap handlers fail
                modalEl.querySelectorAll('.btn-close, [data-bs-dismiss="modal"]').forEach(btn => {
                    btn.addEventListener('click', function () {
                        try {
                            const m = bootstrap.Modal.getInstance(modalEl) || bootstrap.Modal.getOrCreateInstance(modalEl);
                            m.hide();
                        } catch (e) {
                            // fallback cleanup
                            document.querySelectorAll('.modal-backdrop').forEach(el => el.remove());
                            document.body.classList.remove('modal-open');
                        }
                    });
                });
            }
        }

        // Show initial view
        showView('dashboard');
        // Load dashboard statistics
        try { loadDashboardStats(); } catch (e) { console.warn('[ADMIN] loadDashboardStats failed on init', e); }
        // Start queue & stats polling
        try { loadQueueStatus(); startQueuePolling(); startStatsPolling(); loadPendingPayments(); } catch(e){ console.warn('queue/stats polling init failed', e); }
    });

    // ===== DASHBOARD STATS & ACTIVITY =====
    async function loadDashboardStats(){
        try{
            // Total dokter
            const resDoc = await fetch(API_URL + 'dokter.php');
            const docJson = await resDoc.json();
            const totalDokter = (docJson && docJson.success && docJson.data && typeof docJson.data.total === 'number') ? docJson.data.total : (Array.isArray(docJson.data?.dokter) ? docJson.data.dokter.length : 0);
            const elTotal = document.getElementById('totalDokterValue');
            if(elTotal) animateCounter(document.getElementById('totalDokterValue'), totalDokter);

            // Jadwal hari ini
            const weekdays = ['Minggu','Senin','Selasa','Rabu','Kamis','Jumat','Sabtu'];
            const today = new Date();
            const hariNama = weekdays[today.getDay()];
            const resJadwal = await fetch(API_URL + 'jadwal.php?hari=' + encodeURIComponent(hariNama));
            const jadwalJson = await resJadwal.json();
            const jadwalCount = (jadwalJson && jadwalJson.success && Array.isArray(jadwalJson.data)) ? jadwalJson.data.length : 0;
            const elJadwal = document.getElementById('jadwalHariIniValue');
            if(elJadwal) animateCounter(document.getElementById('jadwalHariIniValue'), jadwalCount);

// Fetch server stats (bookings series, doctor changes, etc.)
                try{
                    const resStats = await fetch(API_URL + 'stats.php', { credentials: 'same-origin' });
                    const statsJson = await resStats.json();
                    if (statsJson && statsJson.success && statsJson.data) {
                        const s = statsJson.data;
                        // update totals
                        const pEl = document.getElementById('totalDokterChange'); if(pEl) { pEl.innerText = (s.doctors_pct_change>=0?'+':'') + s.doctors_pct_change + '%'; pEl.classList.toggle('bg-success', s.doctors_pct_change>=0); pEl.classList.toggle('bg-danger', s.doctors_pct_change<0); }
                        const jEl = document.getElementById('jadwalChange'); if(jEl) { jEl.innerText = s.bookings_today; }

                        // animate main counters from server values
                        try{ animateCounter(document.getElementById('totalDokterValue'), s.total_dokter); animateCounter(document.getElementById('jadwalHariIniValue'), s.jadwal_today); }catch(e){ /* ignore */ }

                        // draw sparklines from bookings_last7
                        const series = (s.bookings_last7 || []).map(it => it.count);
                        if(series.length) drawSparklineFromArray('sparkTotalDokter', series);
                        if(series.length) drawSparklineFromArray('sparkJadwal', series);
                    } else {
                        // fallback decorative
                        drawSparkline('sparkTotalDokter', totalDokter);
                        drawSparkline('sparkJadwal', jadwalCount);
                    }
                }catch(e){
                    console.warn('fetch stats failed', e);
                    drawSparkline('sparkTotalDokter', totalDokter);
                    drawSparkline('sparkJadwal', jadwalCount);
                }

            // Aktivitas terakhir — ambil booking terbaru
            const resBooking = await fetch(API_URL + 'booking.php');
            const bookingJson = await resBooking.json();
            const aktivitEl = document.getElementById('activityList');
            if(aktivitEl){
                aktivitEl.innerHTML = '';
                const bookings = bookingJson && bookingJson.success && bookingJson.data && Array.isArray(bookingJson.data.bookings) ? bookingJson.data.bookings : [];
                if(bookings.length === 0){
                    aktivitEl.innerHTML = '<li style="padding:12px;color:#666">Belum ada aktivitas.</li>';
                } else {
                    // take first 6
                    bookings.slice(0,6).forEach(b => {
                        const li = document.createElement('li');
                        li.className = 'activity-item';
                        const tanggal = b.created_at ? b.created_at : (b.tanggal_booking ? b.tanggal_booking : 'Tanggal tidak diketahui');
                        const status = (b.status || '').toLowerCase();
                        let icon = 'bi-person-circle'; let badgeCls = 'bg-secondary';
                        if(status.includes('selesai') || status.includes('seles')) { icon='bi-check-circle-fill'; badgeCls='bg-success'; }
                        else if(status.includes('pending') || status.includes('menunggu')) { icon='bi-clock-fill'; badgeCls='bg-warning'; }
                        else if(status.includes('batal') || status.includes('cancel')) { icon='bi-x-circle-fill'; badgeCls='bg-danger'; }

                        li.innerHTML = `
                            <div class="activity-icon"><i class="bi ${icon}"></i></div>
                            <div class="activity-body">
                              <div class="activity-text"><strong class="muted">${escapeHtml(tanggal)}</strong> — ${escapeHtml(b.nama_pasien || 'Pasien')} <span class="badge ${badgeCls} ms-2">${escapeHtml(b.status || '-')}</span></div>
                              <div class="activity-meta">${escapeHtml(b.tipe_pasien ? b.tipe_pasien : (b.keterangan || ''))}</div>
                            </div>
                        `;
                        aktivitEl.appendChild(li);
                    });
                }
            }

        }catch(err){
            console.error('[ADMIN] loadDashboardStats error', err);
            // Show sensible fallbacks
            const elTotal2 = document.getElementById('totalDokterValue'); if(elTotal2) elTotal2.innerText = '--';
            const elJadwal2 = document.getElementById('jadwalHariIniValue'); if(elJadwal2) elJadwal2.innerText = '--';
            const aktivitEl2 = document.getElementById('activityList'); if(aktivitEl2) aktivitEl2.innerHTML = '<li style="padding:12px;color:#666">Gagal memuat aktivitas.</li>';
        }
    }

    // naive html escape for small text used in activity list
    function escapeHtml(s){ return String(s || '').replace(/[&"'<>]/g, function(m){return {"&":"&amp;","<":"&lt;",">":"&gt;","\'":"&#39;","\"":"&quot;"}[m]; }); }

    // ===== QUEUE STATUS (admin) =====
    async function loadQueueStatus(){
        try{
            const res = await fetch('api/queue.php', { credentials: 'same-origin' });
            if(res.status === 401) return; // not admin
            const json = await res.json();
            if(!json.success) { console.warn('Failed to load queue', json); return; }
            const list = json.data.per_dokter || [];
            const container = document.getElementById('queueList');
            if(!container) return;
            container.innerHTML = '';
            if(list.length === 0) {
                container.innerHTML = '<div class="queue-empty">Tidak ada antrian hari ini.</div>';
                return;
            }
            // Build items
            list.forEach(d => {
                const div = document.createElement('div');
                div.className = 'queue-item';
                const left = document.createElement('div');
                left.innerHTML = `<div><strong>${escapeHtml(d.dokter_nama)}</strong><div class="meta">Menunggu: ${d.waiting_count}</div></div>`;
                const right = document.createElement('div');
                right.style.display = 'flex'; right.style.gap = '10px'; right.style.alignItems = 'center';
                const last = document.createElement('div');
                last.className = 'queue-badge';
                last.title = 'Terakhir selesai';
                last.textContent = d.last_served !== null ? d.last_served : '-';
                const next = document.createElement('div');
                next.className = 'queue-badge';
                next.title = 'Selanjutnya';
                next.style.background = d.next_pending ? '#e7f8ee' : '#f0f6f4';
                next.textContent = d.next_pending !== null ? d.next_pending : '-';
                right.appendChild(last);
                right.appendChild(next);

                // Admin action: mark next pending as selesai
                if (d.next_pending_booking_id) {
                    const btn = document.createElement('button');
                    btn.className = 'btn btn-sm';
                    btn.style.marginLeft = '8px';
                    btn.textContent = 'Selesaikan';
                    btn.addEventListener('click', async function(){
                        if(!confirm('Tandai nomor ' + d.next_pending + ' sebagai selesai?')) return;
                        try{
                            btn.disabled = true;
                            const res = await fetch(API_URL + 'booking.php', {
                                method: 'PUT',
                                credentials: 'same-origin',
                                headers: { 'Content-Type': 'application/json' },
                                body: JSON.stringify({ id: d.next_pending_booking_id, action: 'selesai' })
                            });
                            const json = await res.json();
                            if(json.success){
                                // immediate UI update without waiting for full reload
                                try{
                                    btn.textContent = 'Selesai';
                                    btn.disabled = true;
                                    btn.classList.remove('btn-sm');
                                    btn.classList.add('btn-success');
                                    // update last served badge to the completed number
                                    if(next && last){
                                        last.textContent = next.textContent || (d.next_pending !== null ? d.next_pending : '-');
                                        last.title = 'Terakhir selesai';
                                        // clear next slot
                                        next.textContent = '-';
                                        next.style.background = '#f0f6f4';
                                    }
                                    // decrement waiting count visually
                                    const metaEl = left.querySelector('.meta');
                                    if(metaEl) {
                                        const current = parseInt(String(d.waiting_count || 0), 10);
                                        metaEl.textContent = 'Menunggu: ' + Math.max(0, current - 1);
                                    }
                                    // small inline feedback
                                    const note = document.createElement('span');
                                    note.className = 'small text-success ms-2';
                                    note.textContent = 'ditandai selesai';
                                    btn.parentNode && btn.parentNode.appendChild(note);
                                }catch(e){ console.warn('ui update after complete failed', e); }
                                // refresh queue and recent activity in background
                                await loadQueueStatus();
                                try{ loadDashboardStats(); } catch(e){}
                            } else {
                                alert('Gagal menandai selesai: ' + (json.message || 'error'));
                            }
                        }catch(err){ console.error('complete error', err); alert('Terjadi kesalahan.'); }
                        finally{ btn.disabled = false; }
                    });
                    right.appendChild(btn);
                }
                div.appendChild(left);
                div.appendChild(right);
                container.appendChild(div);
            });
        }catch(e){ console.error('loadQueueStatus error', e); }
    }

    // Poll queue status while on dashboard every 15 seconds
    let __queuePollInterval = null;
    function startQueuePolling(){
        try{ if(__queuePollInterval) clearInterval(__queuePollInterval); __queuePollInterval = setInterval(loadQueueStatus, 15000); } catch(e){}
    }

    // stop polling when leaving dashboard
    function stopQueuePolling(){ try{ if(__queuePollInterval) clearInterval(__queuePollInterval); __queuePollInterval = null; }catch(e){}
    }

    // ===== Dashboard stats polling (so admin doesn't need to F5) =====
    let __statsPollInterval = null;
    function startStatsPolling(){
        try{
            if(__statsPollInterval) clearInterval(__statsPollInterval);
            // only poll when page is visible; use short interval to keep UI fresh
            __statsPollInterval = setInterval(function(){ if(!document.hidden) loadDashboardStats(); }, 20000);
        } catch(e){}
    }
    function stopStatsPolling(){ try{ if(__statsPollInterval) clearInterval(__statsPollInterval); __statsPollInterval = null; }catch(e){}
    }

    // List and confirm payments pending admin verification
    async function loadPendingPayments(){
        try{
            const res = await fetch(API_URL + 'booking.php?status=pending', { credentials: 'same-origin' });
            if(res.status === 401) return;
            const json = await res.json();
            const container = document.getElementById('paymentsList');
            if(!container) return;
            container.innerHTML = '';
            const bookings = json && json.success && json.data && Array.isArray(json.data.bookings) ? json.data.bookings : [];
            // filter bookings that have been paid but not yet confirmed
            const waiting = bookings.filter(b => b.payment_status === 'sudah_bayar' && b.status === 'pending');
            if(waiting.length === 0){ container.innerHTML = '<div class="small text-muted">Tidak ada pembayaran yang menunggu verifikasi.</div>'; return; }

            waiting.forEach(b => {
                const wrapper = document.createElement('div'); wrapper.className = 'd-flex justify-content-between align-items-center mt-2';
                const left = document.createElement('div');
                // compute registration number if possible
                let reg = '';
                try{
                    if(b.created_at && b.id){
                        const dt = new Date(b.created_at.replace(' ', 'T'));
                        const y = dt.getFullYear(); const mo = String(dt.getMonth()+1).padStart(2,'0'); const da = String(dt.getDate()).padStart(2,'0'); const idp = String(b.id).padStart(6,'0');
                        reg = 'REG' + y + mo + da + idp;
                    }
                }catch(e){ reg = ''; }
                left.innerHTML = `<div><strong>${escapeHtml(b.nama_pasien || 'Pasien')}</strong> ${reg ? ('— ' + escapeHtml(reg)) : ''} <div class="muted">Antrian: ${escapeHtml(String(b.nomor_antrian || '-'))} · Metode: ${escapeHtml(b.payment_method || '-')}</div></div>`;
                const right = document.createElement('div');
                const btn = document.createElement('button');
                btn.className = 'btn btn-sm btn-primary'; btn.style.marginLeft = '8px'; btn.textContent = 'Konfirmasi Pembayaran';
                btn.addEventListener('click', async function(){
                    if(!confirm('Konfirmasi pembayaran untuk ' + (b.nama_pasien || 'pasien') + ' (Nomor Antrian ' + (b.nomor_antrian || '-') + ')?')) return;
                    btn.disabled = true;
                    try{
                        const r = await fetch(API_URL + 'booking.php', {
                            method: 'PUT',
                            credentials: 'same-origin',
                            headers: { 'Content-Type': 'application/json' },
                            body: JSON.stringify({ id: b.id, action: 'konfirmasi' })
                        });
                        const jr = await r.json();
                        if(jr.success){
                            btn.textContent = 'Dikonfirmasi';
                            btn.disabled = true;
                            btn.classList.remove('btn-primary'); btn.classList.add('btn-success');
                            // update UI and refresh related lists
                            try{ await loadQueueStatus(); loadDashboardStats(); loadPendingPayments(); } catch(e){}
                        } else { alert('Gagal: ' + (jr.message || 'error')); btn.disabled = false; }
                    }catch(err){ console.error('confirm payment error', err); alert('Terjadi kesalahan.'); btn.disabled = false; }
                });
                right.appendChild(btn);
                wrapper.appendChild(left); wrapper.appendChild(right);
                container.appendChild(wrapper);
            });
        }catch(e){ console.error('[ADMIN] loadPendingPayments error', e); const container = document.getElementById('paymentsList'); if(container) container.innerHTML = '<div class="small text-danger">Gagal memuat pembayaran.</div>'; }
    }

    // ensure we stop polling when page is hidden to avoid unnecessary calls
    document.addEventListener('visibilitychange', function(){
        if(document.hidden){ stopQueuePolling(); stopStatsPolling(); }
        else { if(document.getElementById('view-dashboard') && document.getElementById('view-dashboard').style.display !== 'none') { startQueuePolling(); startStatsPolling(); } }
    });

    // animate numeric counters (simple, no dependency)
    function animateCounter(el, target, duration = 800){
        if(!el) return;
        const span = (el.nodeName && el.nodeName.toLowerCase() === 'span') ? el : (el.querySelector && el.querySelector('.countup')) ? el.querySelector('.countup') : el;
        const start = parseInt(span.innerText.replace(/[^0-9\-]/g,'')) || 0;
        const end = (isFinite(target) ? target : start);
        const range = end - start;
        const startTime = performance.now();
        function step(now){
            const t = Math.min(1, (now - startTime) / duration);
            const val = Math.round(start + range * (t<0.5 ? 2*t*t : -1 + (4 - 2*t)*t)); // ease in-out
            span.innerText = val;
            if(t < 1) requestAnimationFrame(step);
        }
        requestAnimationFrame(step);
    }

    // draw a simple sparkline into an svg element by id using generated samples around baseValue
    function drawSparkline(svgId, baseValue){
        try{
            const svg = document.getElementById(svgId);
            if(!svg) return;
            const w = svg.getAttribute('width') || 120;
            const h = svg.getAttribute('height') || 28;
            const samples = 8;
            const vals = Array.from({length:samples}, (_,i)=> Math.max(0, Math.round(baseValue * (0.5 + Math.random()*0.8))));
            const maxV = Math.max(...vals,1);
            const points = vals.map((v,i)=> {
                const x = Math.round((i/(samples-1)) * (w-4)) + 2;
                const y = Math.round(h - ((v/maxV) * (h-6)) - 2);
                return x+','+y;
            }).join(' ');
            svg.innerHTML = `<polyline points="${points}" stroke="rgba(64,130,109,0.9)" stroke-width="2" fill="none" stroke-linejoin="round" stroke-linecap="round"></polyline>`;
        }catch(e){ /* ignore */ }
    }

    // draw a sparkline from an explicit numeric array (values oldest -> newest)
    function drawSparklineFromArray(svgId, values){
        try{
            const svg = document.getElementById(svgId);
            if(!svg) return;
            const w = parseInt(svg.getAttribute('width') || 120,10);
            const h = parseInt(svg.getAttribute('height') || 28,10);
            const vals = Array.isArray(values) && values.length ? values.slice() : [0];
            const samples = vals.length;
            const maxV = Math.max(...vals, 1);
            const points = vals.map((v,i)=>{
                const x = Math.round((i/(samples-1 || 1)) * (w-4)) + 2;
                const y = Math.round(h - ((v/maxV) * (h-6)) - 2);
                return x+','+y;
            }).join(' ');
            svg.innerHTML = `<polyline points="${points}" stroke="rgba(64,130,109,0.9)" stroke-width="2" fill="none" stroke-linejoin="round" stroke-linecap="round"></polyline>`;
        }catch(e){ console.warn('sparkline draw failed', e); }
    }

    // ===== DOKTER MANAGEMENT =====
    async function loadDokter() {
        try {
            // Read filter value from UI (defaults to 'semua')
            const filterEl = document.getElementById('filterDoctorStatus');
            const statusFilter = filterEl ? (filterEl.value || 'semua') : 'semua';

            // Fetch doctors according to selected filter to display in table
            const response = await fetch(API_URL + 'dokter.php?status=' + encodeURIComponent(statusFilter));
            const data = await response.json();

            if (data.success) {
                displayDokter(data.data.dokter);
            }

            // Always fetch active doctors for schedule selects (they should be active only)
            const responseAktif = await fetch(API_URL + 'dokter.php?status=aktif');
            const dataAktif = await responseAktif.json();

            const selectAdd = document.getElementById('inputDoctorSchedule');
            const selectEdit = document.getElementById('editDoctorSchedule');
            const aktifs = (dataAktif && dataAktif.success && Array.isArray(dataAktif.data.dokter)) ? dataAktif.data.dokter : [];
            console.debug('[ADMIN] loadDokter - aktif doctors for selects =', aktifs.length);

            if (selectAdd) {
                if (aktifs.length === 0) {
                    selectAdd.innerHTML = '<option value="">-- Tidak ada dokter tersedia --</option>';
                    selectAdd.disabled = true;
                } else {
                    selectAdd.disabled = false;
                    selectAdd.innerHTML = '<option value="">-- Pilih Dokter --</option>' + aktifs.map(d => `<option value="${d.id}">${d.nama}</option>`).join('');
                }
            }
            if (selectEdit) {
                selectEdit.innerHTML = '<option value="">-- Pilih Dokter --</option>' + aktifs.map(d => `<option value="${d.id}">${d.nama}</option>`).join('');
            }

        } catch (error) {
            console.error('Error loading dokter:', error);
            alert('Gagal memuat data dokter');
        }
    }

    function displayDokter(dokter) {
        const tbody = document.querySelector('#tableDoctors tbody');
        tbody.innerHTML = '';

        if (dokter.length === 0) {
            tbody.innerHTML = '<tr><td colspan="6" style="text-align:center;padding:20px;">Tidak ada data dokter</td></tr>';
            return;
        }

        dokter.forEach(d => {
            const row = document.createElement('tr');
            row.innerHTML = `
                <td>${d.nama}</td>
                <td>${d.spesialisasi}</td>
                <td>${d.no_telepon || '-'}</td>
                <td><span class="badge bg-${d.status === 'aktif' ? 'success' : d.status === 'cuti' ? 'warning' : 'danger'}">${d.status}</span></td>
                <td class="col-action-140">
                    <a href="booking.php?dokter_id=${d.id}" class="me-1"><button class="btn btn-sm btn-outline-success">Booking</button></a>
                    <button class="btn btn-sm btn-outline-primary" onclick="editDokter(${d.id})">Edit</button>
                    <button class="btn btn-sm btn-outline-danger" onclick="deleteDokter(${d.id})">Hapus</button>
                </td>
            `;
            tbody.appendChild(row);
        });
    }


    window.editDokter = async function(id) {
        // Ambil data dokter berdasarkan id
        try {
            const response = await fetch(API_URL + 'dokter.php?id=' + id);
            const data = await response.json();
            if (data.success && data.data) {
                // API may return either an object (single dokter) or an array (list). Handle both.
                let dokter = data.data;
                if (Array.isArray(dokter)) dokter = dokter[0] || null;
                console.debug('[ADMIN] editDokter response for id=', id, dokter);
                if (!dokter) { alert('Data dokter tidak ditemukan'); return; }

                // Isi form modal edit dokter
                document.getElementById('editDoctorId').value = dokter.id;
                document.getElementById('editDoctorName').value = dokter.nama || '';
                document.getElementById('editSpecialty').value = dokter.spesialisasi || '';
                document.getElementById('editDegree').value = dokter.gelar || '';
                document.getElementById('editSTR').value = dokter.no_str || '';
                document.getElementById('editEmail').value = dokter.email || '';
                document.getElementById('editPhone').value = dokter.no_telepon || '';
                document.getElementById('editStatus').value = dokter.status || 'aktif';
                // Foto telah dihapus dari UI; tidak menampilkan preview atau input file.
                // Photo input removed from UI (no related inputs to reset)
                // Tampilkan modal
                const modal = new bootstrap.Modal(document.getElementById('modalEditDoctor'));
                modal.show();
            } else {
                alert('Data dokter tidak ditemukan');
            }
        } catch (error) {
            alert('Gagal mengambil data dokter');
        }
    };
    // Handle file input untuk foto edit dokter
    // Edit photo input removed — no longer handling photo uploads in Edit Doctor modal

    // Handle form submit untuk edit dokter
    const formEditDoctor = document.getElementById('formEditDoctor');
    if (formEditDoctor) {
        formEditDoctor.addEventListener('submit', handleEditDoctor);
    }

    async function handleEditDoctor(e) {
        e.preventDefault();
        const id = document.getElementById('editDoctorId').value;
        const nama = document.getElementById('editDoctorName').value;
        const spesialisasi = document.getElementById('editSpecialty').value;
        const gelar = document.getElementById('editDegree').value;
        const no_str = document.getElementById('editSTR').value;
        const email = document.getElementById('editEmail').value;
        const no_telepon = document.getElementById('editPhone').value;
        const status = document.getElementById('editStatus').value || 'aktif';
        if (!nama || !spesialisasi) {
            alert('Nama dan spesialisasi wajib diisi');
            return;
        }
        if (!status) {
            alert('Status wajib dipilih');
            return;
        }

        // Foto upload dihapus dari UI; tidak melakukan pemrosesan file di client

        try {
            const response = await fetch(API_URL + 'dokter.php', {
                method: 'PUT',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    id,
                    nama,
                    spesialisasi,
                    gelar,
                    no_str,
                    email,
                    no_telepon,
                    status,

                })
            });
            const data = await response.json();
            if (data.success) {
                alert('Data dokter berhasil diubah');
                document.getElementById('formEditDoctor').reset();
                // Close modal
                const modal = document.getElementById('modalEditDoctor');
                if (modal) {
                    const bsModal = bootstrap.Modal.getInstance(modal);
                    bsModal.hide();
                }
                loadDokter();
            } else {
                alert('Error: ' + data.message);
            }
        } catch (error) {
            alert('Gagal mengubah data dokter');
        }
    }

    window.deleteDokter = function(id) {
        if (confirm('Hapus dokter ini?')) {
            deleteDokterConfirmed(id);
        }
    };

    async function deleteDokterConfirmed(id) {
        try {
            const response = await fetch(API_URL + 'dokter.php', {
                method: 'DELETE',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ id: id })
            });

            const data = await response.json();

            if (data.success) {
                alert('Dokter berhasil dihapus');
                loadDokter();
            } else {
                alert('Error: ' + data.message);
            }
        } catch (error) {
            console.error('Error:', error);
            alert('Gagal menghapus dokter');
        }
    }

    // ===== JADWAL MANAGEMENT =====
    async function loadJadwal() {
        try {
            const response = await fetch(API_URL + 'jadwal.php');
            const data = await response.json();

            if (data.success) {
                displayJadwal(data.data);
            }
        } catch (error) {
            console.error('Error loading jadwal:', error);
        }
    }

    function displayJadwal(jadwal) {
        const tbody = document.querySelector('#tableSchedules tbody');
        tbody.innerHTML = '';

        if (jadwal.length === 0) {
            tbody.innerHTML = '<tr><td colspan="5" style="text-align:center;padding:20px;">Tidak ada jadwal</td></tr>';
            return;
        }

        jadwal.forEach(j => {
            const row = document.createElement('tr');
            row.innerHTML = `
                <td>${j.nama_dokter}</td>
                <td>${j.hari}</td>
                <td>${j.jam_mulai} - ${j.jam_selesai}</td>
                <td>${j.ruangan || '-'}</td>
                <td class="col-action-140">
                    <button class="btn btn-sm btn-outline-primary" onclick="editJadwal(${j.id})">Edit</button>
                    <button class="btn btn-sm btn-outline-danger" onclick="deleteJadwal(${j.id})">Hapus</button>
                </td>
            `;
            tbody.appendChild(row);
        });
    }

        window.editJadwal = async function(id) {
            // Ambil data jadwal berdasarkan id
            try {
                const response = await fetch(API_URL + 'jadwal.php?id=' + id);
                const data = await response.json();
                if (data.success && data.data && data.data.length > 0) {
                    const jadwal = data.data[0];
                    // Isi form modal edit
                    document.getElementById('editScheduleId').value = jadwal.id;
                    document.getElementById('editDoctorSchedule').innerHTML = `<option value="${jadwal.dokter_id}">${jadwal.nama_dokter}</option>`;
                    document.getElementById('editDay').value = jadwal.hari;
                    document.getElementById('editStartTime').value = jadwal.jam_mulai;
                    document.getElementById('editEndTime').value = jadwal.jam_selesai;
                    document.getElementById('editRoom').value = jadwal.ruangan || '';
                    // Tampilkan modal
                    const modal = new bootstrap.Modal(document.getElementById('modalEditSchedule'));
                    modal.show();
                } else {
                    alert('Data jadwal tidak ditemukan');
                }
            } catch (error) {
                alert('Gagal mengambil data jadwal');
            }
        };

    window.deleteJadwal = function(id) {
        if (confirm('Hapus jadwal ini?')) {
            deleteJadwalConfirmed(id);
        }
    };

    async function deleteJadwalConfirmed(id) {
        try {
            const response = await fetch(API_URL + 'jadwal.php', {
                method: 'DELETE',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ id: id })
            });

            const data = await response.json();

            if (data.success) {
                alert('Jadwal berhasil dihapus');
                loadJadwal();
            } else {
                alert('Error: ' + data.message);
            }
        } catch (error) {
            console.error('Error:', error);
            alert('Gagal menghapus jadwal');
        }
    }

    // ===== FORM HANDLERS & EVENT LISTENERS =====
    function setupEventListeners() {
        // Foto upload dihapus — tidak ada penanganan file lagi

        // Bind filter change to reload dokter
        const filterEl = document.getElementById('filterDoctorStatus');
        if (filterEl) {
            filterEl.addEventListener('change', function() {
                loadDokter();
            });
        }

        // Handle form submit untuk tambah dokter
        const formAddDoctor = document.getElementById('formAddDoctor');
        if (formAddDoctor) {
            formAddDoctor.addEventListener('submit', handleAddDoctor);
        }

        // Handle form submit untuk tambah jadwal
        const formAddSchedule = document.getElementById('formAddSchedule');
        if (formAddSchedule) {
            formAddSchedule.addEventListener('submit', handleAddSchedule);
        }

        // Settings: Save profile
        const saveProfileBtn = document.querySelector('#view-pengaturan .btn.btn-primary');
        if (saveProfileBtn) {
            saveProfileBtn.addEventListener('click', handleSaveProfile);
        }

        // Settings: Change password
        const changePassBtn = document.querySelector('#view-pengaturan .btn.btn-danger');
        if (changePassBtn) {
            changePassBtn.addEventListener('click', handleChangePassword);
        }

            // Handle form submit untuk edit jadwal
            const formEditSchedule = document.getElementById('formEditSchedule');
            if (formEditSchedule) {
                formEditSchedule.addEventListener('submit', handleEditSchedule);
            }
    async function handleEditSchedule(e) {
        e.preventDefault();
        const id = document.getElementById('editScheduleId').value;
        const hari = document.getElementById('editDay').value;
        const jam_mulai = document.getElementById('editStartTime').value;
        const jam_selesai = document.getElementById('editEndTime').value;
        const ruangan = document.getElementById('editRoom').value;
        if (!id || !hari || !jam_mulai || !jam_selesai) {
            alert('Hari dan jam wajib diisi');
            return;
        }
        try {
            const response = await fetch(API_URL + 'jadwal.php', {
                method: 'PUT',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    id,
                    hari,
                    jam_mulai,
                    jam_selesai,
                    ruangan
                })
            });
            const data = await response.json();
            if (data.success) {
                alert('Jadwal berhasil diubah');
                document.getElementById('formEditSchedule').reset();
                const modal = document.getElementById('modalEditSchedule');
                if (modal) {
                    const bsModal = bootstrap.Modal.getInstance(modal);
                    bsModal.hide();
                }
                loadJadwal();
            } else {
                alert('Error: ' + data.message);
            }
        } catch (error) {
            alert('Gagal mengubah jadwal');
        }
    }

        // Handle logout with confirmation
        const logoutLink = document.querySelector('.logout-link');
        if (logoutLink) {
            logoutLink.addEventListener('click', function(e) {
                e.preventDefault();
                // Show confirmation prompt before logging out
                if (window.confirm('Yakin ingin keluar?')) {
                    logout();
                }
            });
        }
    }

    async function handleAddDoctor(e) {
        e.preventDefault();

        const nama = document.getElementById('inputDoctorName')?.value;
        const spesialisasi = document.getElementById('inputSpecialty')?.value;
        const gelar = document.getElementById('inputDegree')?.value;
        const no_str = document.getElementById('inputSTR')?.value;
        const email = document.getElementById('inputEmail')?.value;
        const no_telepon = document.getElementById('inputPhone')?.value;
        const status = document.getElementById('inputStatus')?.value || 'aktif';
        // Foto upload telah dihapus dari UI; tidak perlu mengambil input file

        if (!nama || !spesialisasi) {
            alert('Nama dan spesialisasi wajib diisi');
            return;
        }

        if (!status) {
            alert('Status wajib dipilih');
            return;
        }

        // Foto upload telah dihapus; tidak perlu memproses file di client

        try {
            const response = await fetch(API_URL + 'dokter.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    nama,
                    spesialisasi,
                    gelar,
                    no_str,
                    email,
                    no_telepon,
                    status
                })
            });

            const data = await response.json();

            if (data.success) {
                alert('Dokter berhasil ditambahkan');
                document.getElementById('formAddDoctor').reset();

                // Close modal
                const modal = document.getElementById('modalAddDoctor');
                if (modal) {
                    const bsModal = new bootstrap.Modal(modal);
                    bsModal.hide();
                }
                loadDokter();
            } else {
                alert('Error: ' + data.message);
            }
        } catch (error) {
            console.error('Error:', error);
            alert('Gagal menambah dokter');
        }
    }

    async function handleAddSchedule(e) {
        e.preventDefault();

        const dokter_id = document.getElementById('inputDoctorSchedule')?.value;
        const hari = document.getElementById('inputDay')?.value;
        const jam_mulai = document.getElementById('inputStartTime')?.value;
        const jam_selesai = document.getElementById('inputEndTime')?.value;
        const ruangan = document.getElementById('inputRoom')?.value;

        if (!dokter_id || !hari || !jam_mulai || !jam_selesai) {
            alert('Dokter, hari, dan jam wajib diisi');
            return;
        }

        try {
            const response = await fetch(API_URL + 'jadwal.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    dokter_id,
                    hari,
                    jam_mulai,
                    jam_selesai,
                    ruangan,
                    kuota_pasien: 20
                })
            });

            const data = await response.json();

            if (data.success) {
                alert('Jadwal berhasil ditambahkan');
                document.getElementById('formAddSchedule').reset();
                // Close modal
                const modal = document.getElementById('modalAddSchedule');
                if (modal) {
                    const bsModal = new bootstrap.Modal(modal);
                    bsModal.hide();
                }
                loadJadwal();
            } else {
                alert('Error: ' + data.message);
            }
        } catch (error) {
            console.error('Error:', error);
            alert('Gagal menambah jadwal');
        }
    }

    async function logout() {
        try {
            const response = await fetch('api/auth.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ action: 'logout' })
            });

            if (response.ok) {
                window.location.href = 'index.php';
            }
        } catch (error) {
            console.error('Error:', error);
            window.location.href = 'index.php';
        }
    }
})();
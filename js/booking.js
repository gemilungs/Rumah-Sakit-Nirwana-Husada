// === BOOKING FORM SUBMISSION ===
document.addEventListener('DOMContentLoaded', function() {
  const bookingForm = document.getElementById('bookingForm');
  if (bookingForm) {
    bookingForm.addEventListener('submit', async function(e) {
      e.preventDefault();
      // Ambil data form booking
      const dokterId = document.getElementById('dokterId')?.value;
      const tanggal = document.getElementById('tanggal')?.value;
      const jam = document.getElementById('jam')?.value;
      const pasien = document.getElementById('namaPasien')?.value;
      const asuransi = document.getElementById('asuransi')?.value;
      if (!dokterId || !tanggal || !jam || !pasien) {
        alert('Semua data wajib diisi!');
        return;
      }
      try {
        const response = await fetch('api/booking.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({
            dokter_id: dokterId,
            tanggal: tanggal,
            jam: jam,
            nama_pasien: pasien,
            asuransi: asuransi
          })
        });
        const data = await response.json();
        if (data.success) {
          alert('Booking berhasil!');
          bookingForm.reset();
        } else {
          alert('Booking gagal: ' + data.message);
        }
      } catch (error) {
        alert('Terjadi kesalahan saat booking.');
      }
    });
  }

  // === LOAD BOOKING HISTORY ===
  const historyList = document.getElementById('bookingHistory');
  if (historyList) {
    try {
      fetch('api/booking.php?action=history')
        .then(res => res.json())
        .then(data => {
          if (data.success && Array.isArray(data.data)) {
            historyList.innerHTML = '';
            data.data.forEach(item => {
              const li = document.createElement('li');
              li.textContent = `${item.tanggal} - ${item.nama_dokter} - ${item.status}`;
              historyList.appendChild(li);
            });
          } else {
            historyList.innerHTML = '<li>Tidak ada riwayat booking.</li>';
          }
        });
    } catch (error) {
      historyList.innerHTML = '<li>Gagal memuat riwayat booking.</li>';
    }
  }
});
// Data will be fetched from API (dokter / jadwal)
let cachedSpesialis = [];
let preselectedDoctorId = null; // from URL param

function makeAvatarSVG(initials, bg="#eef6ee"){
  const svg = `<svg xmlns='http://www.w3.org/2000/svg' width='120' height='120'>\n  <rect rx='60' ry='60' width='100%' height='100%' fill='${bg}'/>\n  <text x='50%' y='54%' dominant-baseline='middle' text-anchor='middle' font-family='Inter, Arial' font-size='40' fill='#1b4f36' font-weight='700'>${initials}</text>\n  </svg>`;
  return 'data:image/svg+xml;utf8,' + encodeURIComponent(svg);
}

async function loadSpesialisasi(){
  try{
    const res = await fetch('api/dokter.php');
    const json = await res.json();
    if(json.success && json.data && Array.isArray(json.data.spesialisasi_list)){
      cachedSpesialis = json.data.spesialisasi_list;
      // populate select
      if(poliEl){
        poliEl.innerHTML = '<option value="ALL">Semua Poli</option>' + cachedSpesialis.map(s=>`<option value="${s}">${s}</option>`).join('');
      }
    }
  }catch(e){
    console.error('Gagal memuat spesialisasi:', e);
  }
}

// Load single dokter by id (returns object or null)
async function loadDoctorById(id){
  if(!id) return null;
  try{
    const res = await fetch('api/dokter.php?id=' + encodeURIComponent(id));
    const json = await res.json();
    if(json.success && json.data){
      // API might return object or array depending on implementation; handle both
      let doc = json.data;
      if(Array.isArray(doc)) doc = doc[0] || null;
      return doc || null;
    }
  }catch(e){
    console.error('Gagal memuat data dokter id='+id, e);
  }
  return null;
}

/* elements */
const tabs = Array.from(document.querySelectorAll('.big-tab'));
const condAsur = document.getElementById('condAsur');
const condBPJS = document.getElementById('condBPJS');
const providerEl = document.getElementById('provider');
const noPolisEl = document.getElementById('noPolis');

const noBpjsEl = document.getElementById('noBpjs');
const rujukanEl = document.getElementById('rujukan');
const dateEl = document.getElementById('date');
const poliEl = document.getElementById('poli');

const cardsEl = document.getElementById('cards');
const confirmPanel = document.getElementById('confirmPanel');
const confirmTitle = document.getElementById('confirmTitle');
const confirmSlot = document.getElementById('confirmSlot');
const confirmAvatar = document.getElementById('confirmAvatar');
const btnProceed = document.getElementById('btnProceed');
const btnCancel = document.getElementById('btnCancel');
const noteLine = document.getElementById('noteLine');
const errorBox = document.getElementById('errorBox');

let selected = null;
let patientType = 'UMUM';

/* init */
(async function init(){
  const today = new Date();
  const iso = today.toISOString().split('T')[0];
  if(dateEl){ dateEl.value = iso; dateEl.min = iso; }

  // tabs
  tabs.forEach(t => t.addEventListener('click', ()=> setPatientType(t.dataset.tab)));

  setPatientType('UMUM');
  await loadSpesialisasi();

  // Check URL for preselected dokter id (from admin link)
  const params = new URLSearchParams(window.location.search);
  const dokterParam = params.get('dokter_id') || params.get('dokter');
  if(dokterParam){
    preselectedDoctorId = dokterParam;
    const doc = await loadDoctorById(dokterParam);
    if(doc && doc.spesialisasi && poliEl){
      // ensure option exists and select it
      if(!Array.from(poliEl.options).some(o=>o.value === doc.spesialisasi)){
        const opt = document.createElement('option');
        opt.value = doc.spesialisasi;
        opt.text = doc.spesialisasi;
        poliEl.appendChild(opt);
      }
      poliEl.value = doc.spesialisasi;
    }
  }

  await renderCards();
})();

function setPatientType(type){
  patientType = type;
  tabs.forEach(t => t.classList.toggle('active', t.dataset.tab === type));

  if(condAsur) condAsur.style.display = (type === 'ASURANSI') ? 'block' : 'none';
  if(condBPJS) condBPJS.style.display = (type === 'BPJS') ? 'block' : 'none';

  // scroll to BPJS block if selected so user sees top of form (mimic screenshot)
  if(type === 'BPJS' && condBPJS) setTimeout(()=> condBPJS.scrollIntoView({behavior:'smooth', block:'center'}), 80);
}

/* render cards using bottom date/poli (but visually appears above cards) */
dateEl?.addEventListener('change', renderCards);
if(poliEl){ poliEl.addEventListener('change', function(){ preselectedDoctorId = null; renderCards(); }); }

async function renderCards(){
  const dateVal = dateEl?.value;
  if(!dateVal) return;
  const dateObj = new Date(dateVal + 'T00:00:00');
  const weekdayIndex = dateObj.getDay();
  const hariNames = ['Minggu','Senin','Selasa','Rabu','Kamis','Jumat','Sabtu'];
  const hari = hariNames[weekdayIndex];
  const poliFilter = poliEl?.value || 'ALL';

  cardsEl.innerHTML = '';

  try{
    let url = 'api/jadwal.php?hari=' + encodeURIComponent(hari);
    if(preselectedDoctorId) url += '&dokter_id=' + encodeURIComponent(preselectedDoctorId);
    console.debug('[BOOKING] fetching jadwal with url=', url);
    // add cache-busting timestamp to prevent stale cached responses
    url += (url.indexOf('?') === -1 ? '?' : '&') + '_=' + Date.now();
    const res = await fetch(url);
    const json = await res.json();
    if(!json.success || !Array.isArray(json.data)){
      cardsEl.innerHTML = `<div style="grid-column:1/-1;padding:18px;border-radius:8px;background:#fff6f6;border:1px dashed rgba(200,0,0,0.06);color:var(--muted);text-align:center">Gagal memuat jadwal.</div>`;
      return;
    }

    const sessions = [];
    json.data.forEach(j => {
      const start = j.jam_mulai ? j.jam_mulai.slice(0,5) : '';
      const end = j.jam_selesai ? j.jam_selesai.slice(0,5) : '';
      const poli = j.spesialisasi || 'Umum';
      if(poliFilter !== 'ALL' && poli !== poliFilter) return;

      let avatar = '';
      if(j.foto){
        if(/^https?:\/\//i.test(j.foto) || j.foto.startsWith('/')) avatar = j.foto;
        else avatar = '../Media/' + j.foto;
      } else {
        const names = j.nama_dokter ? j.nama_dokter.split(' ').filter(Boolean) : ['Dr'];
        let initials = names.length >= 2 ? (names[0][0] + names[1][0]) : (names[0][0]);
        avatar = makeAvatarSVG(initials.toUpperCase());
      }

      sessions.push({
        doctorId: j.dokter_id,
        doctorName: j.nama_dokter,
        poli: poli,
        specialty: j.ruangan || j.spesialisasi || '',
        avatar: avatar,
        sessionName: j.ruangan || '',
        start: start,
        end: end,
        jadwalId: j.id
      });
    });

    if(sessions.length === 0){
      // No sessions for selected date/poli — fall back to listing doctors for this poli (if filter applied)
      if(poliFilter && poliFilter !== 'ALL'){
        // fetch doctors by spesialisasi
        try{
          const resDoc = await fetch('api/dokter.php?spesialisasi=' + encodeURIComponent(poliFilter));
          const jd = await resDoc.json();
          if(jd.success && jd.data && Array.isArray(jd.data.dokter) && jd.data.dokter.length > 0){
            cardsEl.innerHTML = '';
            jd.data.dokter.forEach(d => {
              const avatar = d.foto ? ((/^https?:\/\//i.test(d.foto) || d.foto.startsWith('/')) ? d.foto : '../Media/' + d.foto) : makeAvatarSVG((d.nama||'D')[0].toUpperCase());
              const card = document.createElement('div');
              card.className = 'card';
              card.innerHTML = `
                <div style="display:flex;gap:12px;align-items:center">
                  <div style="width:48px;height:48px;border-radius:50%;overflow:hidden;background:#eef6ee"><img src="${avatar}" alt="avatar" style="width:100%;height:100%;object-fit:cover"></div>
                  <div style="flex:1;min-width:0">
                    <div style="font-weight:700;color:var(--primary)">${d.nama}</div>
                    <div style="font-size:13px;color:var(--muted)">${d.spesialisasi}</div>
                  </div>
                  <div style="text-align:right">
                    <div style="margin-top:8px"><button class="btn-view btn-outline" style="font-weight:700;padding:6px 10px;border-radius:8px">Lihat Jadwal</button></div>
                  </div>
                </div>
              `;
              const btn = card.querySelector('.btn-view');
              btn.addEventListener('click', async ()=>{
                // Debug: log doctor click
                console.debug('[BOOKING] doctor click, id=', d.id);
                // focus on this doctor's schedule
                preselectedDoctorId = d.id;
                console.debug('[BOOKING] preselectedDoctorId set to', preselectedDoctorId);
                // re-render cards which will call jadwal API for this doctor
                await renderCards();
                // scroll to first match
                const match = cardsEl.querySelector(`.card[data-doctor-id="${d.id}"]`);
                if(match) match.scrollIntoView({behavior:'smooth', block:'center'});
              });
              cardsEl.appendChild(card);
            });
            return;
          }
        }catch(err){
          console.error('Gagal memuat daftar dokter untuk poli', poliFilter, err);
        }
      }

      cardsEl.innerHTML = `<div style="grid-column:1/-1;padding:18px;border-radius:8px;background:#fbfdff;border:1px dashed rgba(2,6,23,0.04);color:var(--muted);text-align:center">Tidak ada sesi pada tanggal ini. Coba tanggal lain atau ubah poli.</div>`;
      return;
    }

    sessions.forEach(sess=>{
      const card = document.createElement('div');
      card.className = 'card';
      card.dataset.doctorId = sess.doctorId;
      card.innerHTML = `
        <div style="display:flex;gap:12px;align-items:center">
          <div style="width:48px;height:48px;border-radius:50%;overflow:hidden;background:#eef6ee"><img src="${sess.avatar}" alt="avatar" style="width:100%;height:100%;object-fit:cover"></div>
          <div style="flex:1;min-width:0">
            <div style="font-weight:700;color:var(--primary)">${sess.doctorName}</div>
            <div style="font-size:13px;color:var(--muted)">${sess.specialty} — ${sess.poli}</div>
          </div>
          <div style="text-align:right">
            <div class="session-time">${sess.start} - ${sess.end}</div>
            <div style="margin-top:8px"><button class="btn-choose btn-outline" style="font-weight:700;padding:6px 10px;border-radius:8px">Pilih</button></div>
          </div>
        </div>
      `;
      const btn = card.querySelector('.btn-choose');
      btn.addEventListener('click', (ev)=>{ ev.stopPropagation(); selectCard(card, sess); });
      card.addEventListener('click', ()=> selectCard(card, sess));
      cardsEl.appendChild(card);
    });

    // If a doctor was opened via URL, auto-select their first session (if any)
    if(preselectedDoctorId){
      const matchCard = cardsEl.querySelector(`.card[data-doctor-id="${preselectedDoctorId}"]`);
      if(matchCard){
        // trigger click to select
        matchCard.click();
      }
    }

  }catch(e){
    cardsEl.innerHTML = `<div style="grid-column:1/-1;padding:18px;border-radius:8px;background:#fff6f6;border:1px dashed rgba(200,0,0,0.06);color:var(--muted);text-align:center">Gagal memuat jadwal.</div>`;
    console.error('Error loading jadwal:', e);
  }
}

/* selection & confirm */
function selectCard(cardEl, sess){
  document.querySelectorAll('.card').forEach(c=>c.classList.remove('selected'));
  cardEl.classList.add('selected');
  selected = {
    doctorId: sess.doctorId, doctorName: sess.doctorName, poli: sess.poli,
    jadwalId: sess.jadwalId,
    session: { name: sess.sessionName, start: sess.start, end: sess.end },
    date: dateEl.value, avatar: sess.avatar
  };
  confirmTitle.textContent = `${sess.doctorName} — ${sess.poli}`;
  confirmSlot.textContent = `${formatDay(dateEl.value)}, ${sess.start} - ${sess.end}`;
  confirmAvatar.innerHTML = `<img src="${sess.avatar}" alt="avatar" style="width:44px;height:44px;object-fit:cover"/>`;
  confirmPanel.style.display = 'flex';
  confirmPanel.scrollIntoView({behavior:'smooth', block:'center'});

  // Prefill confirm inputs from profile if available
  (async function prefillConfirm(){
    try{
      const nameInput = document.getElementById('confirmNama');
      const phoneInput = document.getElementById('confirmPhone');
      if(!nameInput && !phoneInput) return;
      const res = await fetch('api/user.php');
      const j = await res.json();
      if(j.success && j.data){
        if(nameInput && !nameInput.value) nameInput.value = j.data.nama_lengkap || j.data.username || '';
        if(phoneInput && !phoneInput.value) phoneInput.value = j.data.no_telepon || '';
      }
    }catch(e){ /* ignore */ }
  })();
}
function formatDay(isoDate){
  const d = new Date(isoDate + 'T00:00:00');
  return d.toLocaleDateString('id-ID', { weekday:'long', day:'2-digit', month:'short' });
}

btnCancel?.addEventListener('click', ()=>{ selected=null; document.querySelectorAll('.card').forEach(c=>c.classList.remove('selected')); confirmPanel.style.display='none'; confirmAvatar.innerHTML=''; });
  // Prevent default anchor navigation and use our handler to process booking then redirect
  btnProceed?.addEventListener('click', function(e){ e.preventDefault(); onProceed(); });
function onProceed(){
  if(!selected){ showError('Silakan pilih dokter & sesi terlebih dahulu.'); return; }
  if(patientType === 'BPJS'){
    if(!noBpjsEl?.value?.trim()){ showError('Nomor BPJS harus diisi untuk pasien BPJS.'); return; }
    // Upload Kartu BPJS field dihapus => tidak memeriksa file lagi
  }
  if(patientType === 'ASURANSI'){
    if(!providerEl?.value){ showError('Pilih provider asuransi.'); return; }
    if(!noPolisEl?.value?.trim()){ showError('Nomor polis asuransi harus diisi.'); return; }
    // Upload Kartu Asuransi dihapus => tidak memeriksa file lagi
  }

  const payload = {
    patient: { gender: null, patientType },
    booking: { doctorId: selected.doctorId, doctorName: selected.doctorName, poli: selected.poli, date: selected.date, session: selected.session },
    extras: {}
  };
  if(patientType === 'BPJS'){
    payload.extras.bpjsNo = noBpjsEl?.value?.trim();
    payload.extras.rujukanFile = rujukanEl?.files?.length ? rujukanEl.files[0].name : null;
  }
  if(patientType === 'ASURANSI'){
    payload.extras.provider = providerEl?.value;
    payload.extras.noPolis = noPolisEl?.value?.trim();
  }
  // Show non-blocking processing indicator
  showProcessing('Memproses pesanan...');

  // Create booking via API, then redirect to payment page with booking_id
  (async function createBookingAndRedirect(){
    try{
      // Fetch current user profile to populate required fields
      const userRes = await fetch('api/user.php');
      const userJson = await userRes.json();
      if(!userJson.success){ throw new Error('Gagal mengambil profil pengguna'); }
      const user = userJson.data || {};

      // prefer values entered in confirm panel, fall back to profile
      const confirmNama = document.getElementById('confirmNama')?.value?.trim();
      const confirmPhone = document.getElementById('confirmPhone')?.value?.trim();

      const body = {
        dokter_id: selected.doctorId,
        jadwal_id: selected.jadwalId || null,
        tanggal_booking: selected.date,
        nama_pasien: confirmNama || user.nama_lengkap || user.username || '',
        no_telepon: confirmPhone || user.no_telepon || '',
        tipe_pasien: patientType || 'UMUM'
      };

      // validate name/phone presence (api requires both)
      if(!body.nama_pasien || !body.no_telepon){
        throw new Error('Nama pasien dan nomor telepon wajib diisi. Silakan lengkapi pada panel konfirmasi.');
      }

      if (patientType === 'ASURANSI') {
        body.provider_asuransi = providerEl?.value || null;
        body.nomor_polis = noPolisEl?.value?.trim() || null;
      }
      if (patientType === 'BPJS') {
        body.nomor_bpjs = noBpjsEl?.value?.trim() || null;
      }

      const res = await fetch('api/booking.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(body)
      });
      const data = await res.json();
      if(!data.success){ throw new Error(data.message || 'Gagal membuat booking'); }
      const bookingId = data.data.id;
      hideProcessing();
      // Redirect to payment page with booking id
      const params = new URLSearchParams();
      params.set('booking_id', bookingId);
      window.location.href = 'payment.php?' + params.toString();
    }catch(err){
      hideProcessing();
      showError(err.message || 'Gagal membuat booking.');
    }
  })();
}

function showError(msg){
  if(errorBox){ errorBox.style.display = 'block'; errorBox.textContent = 'Error: ' + msg; errorBox.scrollIntoView({behavior:'smooth', block:'center'}); } else alert(msg);
}

// Small processing overlay helpers
function showProcessing(msg){
  let el = document.getElementById('processingOverlay');
  if(!el){
    el = document.createElement('div');
    el.id = 'processingOverlay';
    el.style.position = 'fixed';
    el.style.left = '50%';
    el.style.top = '20%';
    el.style.transform = 'translate(-50%, -50%)';
    el.style.background = 'rgba(0,0,0,0.85)';
    el.style.color = '#e6ffe6';
    el.style.padding = '18px 24px';
    el.style.borderRadius = '10px';
    el.style.zIndex = 99999;
    el.style.maxWidth = '90%';
    el.style.boxShadow = '0 10px 40px rgba(0,0,0,0.6)';
    el.style.fontFamily = 'Encode Sans, Arial, sans-serif';
    el.style.fontSize = '14px';
    document.body.appendChild(el);
  }
  el.textContent = msg || 'Memproses...';
  el.style.display = 'block';
}
function hideProcessing(){
  const el = document.getElementById('processingOverlay');
  if(el) el.style.display = 'none';
}
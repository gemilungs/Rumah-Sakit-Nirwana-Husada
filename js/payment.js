// === PAYMENT FORM SUBMISSION ===
document.addEventListener('DOMContentLoaded', function() {
  const paymentForm = document.getElementById('paymentForm');
  // Booking / amount helpers
  let currentBooking = null;
  const adminFee = 75000;
  const procedureFee = 79000;

  async function loadBookingAndInit(){
    const params = new URLSearchParams(window.location.search);
    const bookingId = params.get('booking_id');
    if(!bookingId) return;
    try{
      const res = await fetch('api/booking.php?id=' + encodeURIComponent(bookingId), { credentials: 'same-origin' });
      if (res.status === 401) { window.location.href = 'login.php?next=' + encodeURIComponent(window.location.pathname + window.location.search); return; }
      const json = await res.json();
      if(!json.success){ console.warn('Booking tidak ditemukan', json); return; }
      currentBooking = json.data;
      // populate summary lines
      document.getElementById('lineAdmin').textContent = 'IDR ' + adminFee.toLocaleString('id-ID');
      document.getElementById('lineProcedure').textContent = 'IDR ' + procedureFee.toLocaleString('id-ID');
      const dokterFee = Number(currentBooking.biaya_konsultasi || 0);
      document.getElementById('lineDokter').textContent = 'IDR ' + dokterFee.toLocaleString('id-ID');
      // Show insurance / bpjs info if present
      const infoEl = document.getElementById('insuranceInfo');
      if(currentBooking.tipe_pasien === 'ASURANSI'){
        infoEl.style.display = 'block';
        infoEl.textContent = `Asuransi: ${currentBooking.provider_asuransi || '-'} — No. Polis: ${currentBooking.nomor_polis || '-'} `;
      } else if(currentBooking.tipe_pasien === 'BPJS'){
        infoEl.style.display = 'block';
        infoEl.textContent = `BPJS No: ${currentBooking.nomor_bpjs || '-'} — Kelas: ${currentBooking.kelas_bpjs || '-'}`;
      } else {
        infoEl.style.display = 'none';
      }
      // Update visible golongan label & hidden input
      const displayGol = document.getElementById('displayGolongan');
      const hiddenGol = document.getElementById('golonganHidden');
      if(displayGol) displayGol.textContent = (currentBooking.tipe_pasien || 'UMUM');
      if(hiddenGol) hiddenGol.value = (currentBooking.tipe_pasien || 'UMUM');

      // Show nomor antrian on page/receipt area
      try{
        const anEl = document.getElementById('nomorAntrian');
        if(anEl) anEl.textContent = currentBooking.nomor_antrian || '-';
      }catch(e){/* ignore */}

      calculateTotals();

      // Payment deadline: compute 10 minutes from booking.created_at and start live countdown (short test window)
      try{
        const dueTextEl = document.getElementById('dueText');
        const dueCountdownEl = document.getElementById('dueCountdown');
        const createdAtRaw = currentBooking.created_at_iso || currentBooking.created_at; // prefer ISO from server if available
        if(createdAtRaw && dueTextEl && dueCountdownEl) {
          let deadlineTs;
          const FIXED_OFFSET_MS = (7 * 60 * 60 * 1000) + (10 * 60 * 1000); // 7 hours + 10 minutes (workaround for Hostinger timezone gap)
          // Prefer server-computed deadline in milliseconds when available to avoid timezone issues
          if (typeof currentBooking.payment_deadline_ts !== 'undefined' && currentBooking.payment_deadline_ts) {
            deadlineTs = Number(currentBooking.payment_deadline_ts);
          } else {
            const createdISO = (currentBooking.created_at_iso) ? currentBooking.created_at_iso : String(createdAtRaw).replace(' ', 'T');
            deadlineTs = (new Date(createdISO)).getTime() + (10 * 60 * 1000); // 10 minutes
          }
          // Apply Hostinger offset so countdown matches hosted server (+7h10m)
          deadlineTs = deadlineTs + FIXED_OFFSET_MS;
          // persist for other handlers (e.g., form submit validation)
          window.__paymentDeadlineTs = deadlineTs;
          // Format deadline string in Indonesian style
          const dt = new Date(deadlineTs);
          const months = ['Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'];
          const formatted = `BAYAR SEBELUM ${dt.getDate()} ${months[dt.getMonth()]} ${dt.getFullYear()} PUKUL ${String(dt.getHours()).padStart(2,'0')}:${String(dt.getMinutes()).padStart(2,'0')}`;

          // If already paid, show paid status instead
          if (currentBooking.payment_status === 'sudah_bayar') {
            dueTextEl.textContent = 'Pembayaran sudah diterima';
            dueCountdownEl.textContent = '';
            const payBtn = document.getElementById('payBtn'); if(payBtn) payBtn.disabled = true;

            // fetch last payment info for this booking (to show transaction number / printable receipt)
            try{
              fetch('api/payment.php?booking_id=' + encodeURIComponent(currentBooking.id), { credentials: 'same-origin' })
                .then(r => r.json())
                .then(j => {
                  if(j.success && j.data && j.data.payment){
                    const txn = j.data.payment.transaction_no;
                    if(txn){ const txnEl = document.getElementById('transactionNo'); if(txnEl) txnEl.textContent = txn; const printBtn = document.getElementById('printReceiptBtn'); if(printBtn) printBtn.style.display = 'inline-block'; }
                  }
                }).catch(()=>{});
            }catch(e){}

          } else {
            dueTextEl.textContent = formatted;

            // countdown updater
            function updateCountdown(){
              const now = Date.now();
              const diff = deadlineTs - now;
              if(diff <= 0){
                dueCountdownEl.textContent = ' (Waktu pembayaran telah habis)';
                // mark expired visually
                dueTextEl.parentNode && dueTextEl.parentNode.classList.add('expired');
                // disable payment button
                const payBtn = document.getElementById('payBtn'); if(payBtn) payBtn.disabled = true;
                // hide any payment details or confirm button (VA flows)
                const confirmPaid = document.getElementById('confirmPaidBtn'); if(confirmPaid) confirmPaid.style.display = 'none';
                const details = document.getElementById('paymentDetails'); if(details) details.style.display = 'none';
                clearInterval(window.__paymentCountdownInterval);
                return;
              }
              const h = Math.floor(diff / (1000*60*60));
              const m = Math.floor((diff % (1000*60*60)) / (1000*60));
              const s = Math.floor((diff % (1000*60)) / 1000);
              dueCountdownEl.textContent = ` (sisa ${String(h).padStart(2,'0')}j ${String(m).padStart(2,'0')}m ${String(s).padStart(2,'0')}s)`;
            }

            updateCountdown();
            if(window.__paymentCountdownInterval) clearInterval(window.__paymentCountdownInterval);
            window.__paymentCountdownInterval = setInterval(updateCountdown, 1000);
          }
        }
      }catch(e){ console.warn('deadline calc failed', e); }
    }catch(e){ console.error('Gagal memuat booking', e); }
  }

  function getSelectedGolongan(){
    // Use booking's tipe_pasien as the single source of truth (fall back to hidden input / UMUM)
    return (currentBooking && currentBooking.tipe_pasien) ? currentBooking.tipe_pasien : (document.getElementById('golonganHidden')?.value || 'UMUM');
  }

  function calculateTotals(){
    const dokterFee = Number(currentBooking?.biaya_konsultasi || 0);
    const subtotal = adminFee + procedureFee + dokterFee;
    let covered = 0;
    let patientPays = 0;
    const gol = getSelectedGolongan();
    if(gol === 'UMUM'){
      covered = 0;
      patientPays = subtotal;
    }else if(gol === 'ASURANSI'){
      // Simple rule: Asuransi menanggung 90% dari jasa dokter + procedure, admin ditanggung pasien
      const coverable = procedureFee + dokterFee;
      covered = Math.round(coverable * 0.9);
      patientPays = subtotal - covered;
    }else if(gol === 'BPJS'){
      // BPJS menanggung jasa dokter + procedure sepenuhnya, pasien hanya bayar admin fee
      covered = procedureFee + dokterFee;
      patientPays = adminFee;
    }
    // Update DOM
    document.getElementById('coveredAmount').textContent = 'IDR ' + covered.toLocaleString('id-ID');
    document.getElementById('patientPay').textContent = 'IDR ' + patientPays.toLocaleString('id-ID');
    document.getElementById('totalAmount').textContent = 'IDR ' + patientPays.toLocaleString('id-ID');
    document.getElementById('summaryTotal').textContent = 'IDR ' + patientPays.toLocaleString('id-ID');
    // Also set hidden amount for submission (numeric value)
    document.getElementById('amount').value = patientPays;
  }

  // No golongan radios on this page - totals driven by booking.tipe_pasien. Recalculate when booking data loads.
  // (Left intentionally blank)

  if (paymentForm) {
    paymentForm.addEventListener('submit', async function(e) {
      e.preventDefault();      // prevent submission if deadline passed
      if(window.__paymentDeadlineTs && Date.now() > window.__paymentDeadlineTs){ alert('Waktu pembayaran telah habis. Silakan buat booking baru.'); return; }      const method = document.querySelector('.method.active')?.getAttribute('data-method');
      const amount = document.getElementById('amount')?.value;
      const params = new URLSearchParams(window.location.search);
      const bookingId = params.get('booking_id');
      if (!method || (!amount && amount !== '0')) {
        alert('Pilih metode dan pastikan jumlah pembayaran terhitung.');
        return;
      }
      try {
        const btn = document.getElementById('payBtn');
        if (btn) btn.disabled = true;
        const body = { method: method, amount: amount };
        if (bookingId) body.booking_id = bookingId;

        // Special handling for Virtual Account: generate and show VA details instead of auto-mark paid
        if (method === 'va') {
          const bank = document.querySelector('input[name="bank"]:checked')?.value;
          if (!bank) { alert('Pilih bank untuk Virtual Account'); if (btn) btn.disabled = false; return; }
          // generate pseudo VA number
          const vaNumber = generateVANumber(bank, bookingId);
          const reg = currentBooking?.registration_number ? `<div>Nomor Registrasi: <strong>${currentBooking.registration_number}</strong></div>` : '';
          const an = currentBooking?.nomor_antrian ? `<div>Nomor Antrian: <strong>${currentBooking.nomor_antrian}</strong></div>` : '';
          showPaymentInstructions(`Virtual Account (${bank.toUpperCase()}): <strong>${vaNumber}</strong><br/>Silakan transfer sesuai jumlah ke nomor VA ini. Setelah transfer, klik "Saya sudah membayar".<br/>${reg}${an}`);
          // keep bookingId and method for confirmation
          document.getElementById('confirmPaidBtn').dataset.bookingId = bookingId || '';
          document.getElementById('confirmPaidBtn').dataset.method = method + '_' + bank;
          document.getElementById('confirmPaidBtn').style.display = 'inline-block';
          if (btn) btn.disabled = false;
          return;
        }

        // For non-VA methods, use Midtrans Snap flow
        const createResp = await fetch('api/create_snap.php', {
          method: 'POST',
          credentials: 'same-origin',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ booking_id: bookingId, method: method })
        });
        if (createResp.status === 401) { alert('Silakan login terlebih dahulu.'); window.location.href = 'login.php?next=' + encodeURIComponent(window.location.pathname + window.location.search); return; }
        const createData = await createResp.json();
        if(!createData.success){ alert('Gagal memulai pembayaran: ' + createData.message); if(btn) btn.disabled = false; return; }
        const snapToken = createData.data?.snap_token;
        const orderId = createData.data?.order_id;
        if(!snapToken){ alert('Gagal memperoleh token pembayaran'); if(btn) btn.disabled = false; return; }

        // open Midtrans Snap modal
        try{
          window.snap.pay(snapToken, {
            onSuccess: function(result){
              (async ()=>{
                try{
                  const confResp = await fetch('api/payment_confirm.php', {
                    method: 'POST',
                    credentials: 'same-origin',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ booking_id: bookingId, order_id: result.order_id, transaction_id: result.transaction_id, amount: amount })
                  });
                  const conf = await confResp.json();
                  if(conf.success){
                    const reg = currentBooking.registration_number ? `<div>Nomor Registrasi: <strong>${currentBooking.registration_number}</strong></div>` : '';
                    const an = currentBooking.nomor_antrian ? `<div>Nomor Antrian: <strong>${currentBooking.nomor_antrian}</strong></div>` : '';
                    const txn = result.transaction_id || result.order_id;
                    const txnEl = document.getElementById('transactionNo'); if(txnEl) txnEl.textContent = txn;
                    const printBtn = document.getElementById('printReceiptBtn'); if(printBtn) printBtn.style.display = 'inline-block';
                    showPaymentInstructions(`Pembayaran terverifikasi. Terima kasih.<br/>${reg}${an}`);
                    setTimeout(()=> window.location.href = 'profile.php', 2200);
                  } else {
                    alert('Konfirmasi pembayaran: ' + conf.message);
                  }
                }catch(e){ console.error('confirm error', e); alert('Terjadi kesalahan saat mengkonfirmasi pembayaran.'); }
              })();
            },
            onPending: function(result){
              const txn = result.transaction_id || result.order_id;
              const reg = currentBooking.registration_number ? `<div>Nomor Registrasi: <strong>${currentBooking.registration_number}</strong></div>` : '';
              const an = currentBooking.nomor_antrian ? `<div>Nomor Antrian: <strong>${currentBooking.nomor_antrian}</strong></div>` : '';
              const msg = `Pembayaran tertunda (status: ${result.transaction_status}). Nomor transaksi: <strong>${txn}</strong>. Anda akan diberitahu setelah pembayaran berhasil.<br/>${reg}${an}`;
              showPaymentInstructions(msg);
              const txnEl = document.getElementById('transactionNo'); if(txnEl) txnEl.textContent = txn;
              const printBtn = document.getElementById('printReceiptBtn'); if(printBtn) printBtn.style.display = 'inline-block';
              if(btn) btn.disabled = false;
            },
            onError: function(err){
              console.error('Snap error', err);
              alert('Pembayaran gagal: ' + (err?.message || 'Kesalahan midtrans'));
              if(btn) btn.disabled = false;
            },
            onClose: function(){ if(btn) btn.disabled = false; }
          });
        }catch(e){ console.error('snap.pay error', e); alert('Gagal membuka Midtrans. Coba lagi.'); if(btn) btn.disabled = false; }
      } catch (error) {
        console.error('Payment error', error);
        // Prefer showing a helpful message when available
        const msg = (error && error.message) ? error.message : (error && typeof error === 'string' ? error : 'Terjadi kesalahan saat pembayaran. Silakan cek Console / Network untuk detail.');
        alert(msg);
        const btn = document.getElementById('payBtn'); if (btn) btn.disabled = false;
      }
    });
  }

  // helper: show payment instructions area
  function showPaymentInstructions(html){
    const details = document.getElementById('paymentDetails');
    const content = document.getElementById('paymentDetailsContent');
    if(!details || !content) return;
    content.innerHTML = html;
    details.style.display = 'block';
  }

  // generate pseudo virtual account number
  function generateVANumber(bank, bookingId){
    const bankCodes = { bca: '700', bni: '800', mandiri: '900', bri: '600' };
    const code = bankCodes[bank] || '999';
    const ts = Date.now().toString().slice(-8);
    return code + ts + String(bookingId).padStart(4,'0');
  }

  // confirm paid button handler (used for VA)
  const confirmPaidBtn = document.getElementById('confirmPaidBtn');
  if(confirmPaidBtn){
    confirmPaidBtn.addEventListener('click', async function(){
      const bId = this.dataset.bookingId;
      const method = this.dataset.method || 'va';
      if(!bId){ alert('Booking tidak ditemukan'); return; }
      try{
        const res = await fetch('api/payment.php', {
          method: 'POST',
          credentials: 'same-origin',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ booking_id: bId, method: method, amount: document.getElementById('amount')?.value || 0 })
        });
        const j = await res.json();
        if(j.success){
          const reg = currentBooking?.registration_number ? `<div>Nomor Registrasi: <strong>${currentBooking.registration_number}</strong></div>` : '';
          const an = currentBooking?.nomor_antrian ? `<div>Nomor Antrian: <strong>${currentBooking.nomor_antrian}</strong></div>` : '';
          const txn = j.data?.transaction_no ? j.data.transaction_no : null;
          if(txn){
            const txnEl = document.getElementById('transactionNo'); if(txnEl) txnEl.textContent = txn;
            const printBtn = document.getElementById('printReceiptBtn'); if(printBtn) printBtn.style.display = 'inline-block';
          }
          showPaymentInstructions(`Pembayaran berhasil diverifikasi. Terima kasih.<br/>${reg}${an}`);
          setTimeout(()=> window.location.href = 'profile.php', 1200);
        } else {
          alert('Konfirmasi pembayaran gagal: ' + j.message);
        }
      }catch(e){ console.error('confirmPaid error', e); alert('Terjadi kesalahan saat mengkonfirmasi pembayaran.'); }
    });
  }

  // On initial page load try to fetch booking
  loadBookingAndInit();
});
const methodEls = document.querySelectorAll('.method');
const dompetPanel = document.getElementById('dompetPanel');
const qrisPanel = document.getElementById('qrisPanel');
const vaPanel = document.getElementById('vaPanel');

let selectedMethod = 'dompet';

function showPanel(method) {
  selectedMethod = method;
  dompetPanel.style.display = method === 'dompet' ? 'block' : 'none';
  qrisPanel.style.display = method === 'qris' ? 'block' : 'none'; 
  vaPanel.style.display = method === 'va' ? 'block' : 'none';
}

methodEls.forEach(el => {
  el.addEventListener('click', () => {
  methodEls.forEach(x => x.classList.remove('active'));
  el.classList.add('active');
  showPanel(el.getAttribute('data-method'));
  // hide any prior payment details when changing method
  const details = document.getElementById('paymentDetails'); if(details) details.style.display = 'none';
  const confirmPaid = document.getElementById('confirmPaidBtn'); if(confirmPaid) confirmPaid.style.display = 'none';
  });

  el.addEventListener('keydown', (e) => {
  if(e.key === 'Enter' || e.key === ' ') { e.preventDefault(); el.click(); }
  });
});

document.addEventListener('DOMContentLoaded', () => {
  const active = document.querySelector('.method.active');
  if(active) showPanel(active.getAttribute('data-method'));

  // Print receipt button
  const printBtn = document.getElementById('printReceiptBtn');
  if(printBtn){
    printBtn.addEventListener('click', function(){
      window.print();
    });
  }

  // Email receipt button
  const emailBtn = document.getElementById('emailReceiptBtn');
  const emailStatus = document.getElementById('emailSentStatus');
  if(emailBtn){
    emailBtn.addEventListener('click', async function(){
      try{
        const params = new URLSearchParams(window.location.search);
        const bookingId = params.get('booking_id');
        if(!bookingId) { alert('Booking tidak ditemukan'); return; }
        emailBtn.disabled = true; emailBtn.textContent = 'Mengirim...';
        const res = await fetch('api/send_receipt.php', {
          method: 'POST',
          credentials: 'same-origin',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ booking_id: bookingId })
        });
        const j = await res.json();
        if(j.success){
          emailStatus.textContent = 'Struk telah dikirim ke ' + (j.data.email || 'email Anda');
          emailBtn.textContent = 'Terkirim';
          emailBtn.style.display = 'none';
        } else {
          alert('Gagal mengirim: ' + j.message);
          emailBtn.disabled = false; emailBtn.textContent = 'Kirim Struk via Email';
        }
      }catch(e){
        console.error('send email error', e);
        alert('Terjadi kesalahan saat mengirim email.');
        emailBtn.disabled = false; emailBtn.textContent = 'Kirim Struk via Email';
      }
    });
  }
});
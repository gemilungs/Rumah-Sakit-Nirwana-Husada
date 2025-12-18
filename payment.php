<?php 
  require_once 'config.php'; require_once 'lib/session_utils.php'; hydrateSessionFromDB(); 
  // Midtrans server-side interactions are handled in `api/create_snap.php`, `api/payment_confirm.php`, and `midtrans_notify.php`.
  // The client Snap JS (sandbox) is included in the HTML head with your client key.

?>
<!doctype html>
<html lang="id">
<head>
<meta charset="utf-8" />
<meta name="viewport" content="width=device-width,initial-scale=1" />
<title>Pembayaran</title>

    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

    <!-- Google Font -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@400..900&family=Encode+Sans:wght@100..900&display=swap" rel="stylesheet">

    <!-- CSS -->
    <link rel="stylesheet" href="../css/payment.css">

    <!-- Favicon -->
    <link rel="icon" type="img/png" href="../Media/logo1.png">

    <!-- Midtrans Snap (sandbox) -->
    <script src="https://app.sandbox.midtrans.com/snap/snap.js" data-client-key="Mid-client-EbVXYXxpvKa3SBuA"></script>

</head>
<body>
  <!-- Header intentionally removed on payment page for a focused checkout experience -->
  <div style="height:18px"></div>
  <div class="container">
    <main class="card" role="main">
      <h2>Pembayaran</h2>

      <div class="total-wrap">
        <div class="due"><span id="dueText">Memuat tenggat pembayaran…</span> <span id="dueCountdown" class="countdown"></span></div>
        <div class="total" id="totalAmount">IDR 354.000</div>
      </div>

      <div style="margin-top:10px;">
        <strong>Nomor Antrian: </strong> <span id="nomorAntrian">-</span>
      </div>

      <div>Golongan Pasien</div>
      <div style="margin-bottom:12px;display:flex;gap:12px;align-items:center">
        <div id="displayGolongan" style="font-weight:600;color:#123;">Umum</div>
        <input type="hidden" id="golonganHidden" name="golongan" value="UMUM" />
        <div id="insuranceInfo" style="margin-top:0;font-size:13px;color:#444"></div>
      </div>

      <div >Metode Pembayaran</div>

      <form id="paymentForm">
      <div class="methods" id="methods">
        <div class="method active" data-method="dompet" tabindex="0">
          <div class="icon">WD</div>
          <div>
            <h3>Dompet Digital</h3>
            <p>GoPay · OVO · ShopeePay</p>
          </div>
        </div>

        <div class="method" data-method="qris" tabindex="0">
          <div class="icon">QR</div>
          <div>
            <h3>QRIS</h3>
            <p>Scan dengan dompet</p>
          </div>
        </div>

        <div class="method" data-method="va" tabindex="0">
          <div class="icon">BK</div>
          <div>
            <h3>Virtual Account</h3>
            <p>Pilih 1 bank</p>
          </div>
        </div>
      </div>

      <!-- SINGLE PANEL AREA -->
      <div class="panel" id="panelArea">
        <!-- Dompet Digital options (default visible) -->
        <div id="dompetPanel">
          <h4>Pilih Dompet Digital</h4>
          <div class="row" id="dompetOptions" style="margin-top:10px">
            <label class="option"><input type="radio" name="wallet" value="gopay" checked> GoPay</label>
            <label class="option"><input type="radio" name="wallet" value="ovo"> OVO</label>
            <label class="option"><input type="radio" name="wallet" value="shopeepay"> ShopeePay</label>
          </div>
        </div>

        <!-- QRIS Panel (NOW EMPTY: nothing but the panel wrapper) -->
        <div id="qrisPanel" style="display:none"></div>

        <!-- VA Panel (ONLY bank radios, NO VA generation) -->
        <div id="vaPanel" style="display:none">
          <h4>Pilih Bank (Virtual Account)</h4>
          <div class="row" id="vaOptions" style="margin-top:10px">
            <label class="option"><input type="radio" name="bank" value="bca"> BCA</label>
            <label class="option"><input type="radio" name="bank" value="bni"> BNI</label>
            <label class="option"><input type="radio" name="bank" value="mandiri"> Mandiri</label>
            <label class="option"><input type="radio" name="bank" value="bri"> BRI</label>
          </div>
        </div>

        <input type="hidden" id="amount" name="amount" value="" />
        <div id="paymentDetails" style="display:none;margin-top:14px;padding:12px;border-radius:10px;border:1px solid #eef3f6;background:#fbfdff">
          <!-- dynamic payment instructions (VA number, QR code, etc) -->
          <div id="paymentDetailsContent"></div>
          <div style="margin-top:12px">
            <button id="confirmPaidBtn" class="btn" style="display:none">Saya sudah membayar</button>
          </div>
        </div>
        <div class="btn-row">
          <button class="btn" id="payBtn" type="submit">Lanjutkan ke Pembayaran</button>
        </div>
      </form>
      </div>
    </main>

    <aside class="summary" aria-label="Ringkasan Pesanan">
      <h3>Ringkasan Pesanan</h3>
      <small>Transaksi #: <span id="transactionNo">-</span></small>
      <div style="margin-top:10px;display:flex;gap:8px">
        <button id="printReceiptBtn" class="btn" style="display:none">Cetak Struk</button>
        <button id="emailReceiptBtn" class="btn" style="display:none;background:linear-gradient(90deg,#2b6a9a,#3aa1c9)">Kirim Struk via Email</button>
      </div>
      <div id="emailSentStatus" style="margin-top:8px;font-size:13px;color:#1f785f"></div>

      <div class="line"></div>

      <div class="item"><div class="title">Deskripsi</div><div></div></div>
      <div class="item"><div class="title">Administrasi [RJ/RD]</div><div id="lineAdmin">IDR 75.000</div></div>
      <div class="item"><div class="title">Manual Akupunktur</div><div id="lineProcedure">IDR 79.000</div></div>
      <div class="item"><div class="title">Jasa Dokter</div><div id="lineDokter">IDR 200.000</div></div>
      <div class="line"></div>
      <div class="item"><div class="title">Ditanggung Asuransi/BPJS</div><div id="coveredAmount">IDR 0</div></div>
      <div class="item"><div class="title">Harus Dibayar Pasien</div><div id="patientPay">IDR 354.000</div></div>

      <div class="line"></div>

      <div class="total-row"><div>Jumlah Total</div><div id="summaryTotal">IDR 354.000</div></div>
    </aside>
  </div>

    <script src="../js/payment.js"> </script>

</body>
</html>

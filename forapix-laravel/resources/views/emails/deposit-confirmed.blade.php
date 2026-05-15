<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Depósito confirmado</title>
<style>
  body { margin:0; padding:0; background:#0f0f1a; font-family:'Segoe UI',Arial,sans-serif; color:#e5e7eb; }
  .wrap { max-width:560px; margin:32px auto; background:#1a1d3a; border-radius:16px; overflow:hidden; }
  .header { background:linear-gradient(135deg,#7c3aed,#8b5cf6); padding:32px 28px; text-align:center; }
  .header h1 { margin:0 0 4px; font-size:22px; color:#fff; }
  .header p  { margin:0; font-size:14px; color:rgba(255,255,255,.75); }
  .body { padding:28px; }
  .amount-box { background:rgba(34,197,94,.1); border:1px solid rgba(34,197,94,.3); border-radius:12px; padding:20px; text-align:center; margin-bottom:24px; }
  .amount-box .label { font-size:12px; color:#9ca3af; text-transform:uppercase; letter-spacing:.08em; }
  .amount-box .value { font-size:36px; font-weight:700; color:#4ade80; margin:4px 0 0; }
  .info-row { display:flex; justify-content:space-between; padding:10px 0; border-bottom:1px solid rgba(255,255,255,.06); font-size:14px; }
  .info-row:last-child { border-bottom:none; }
  .info-row .k { color:#9ca3af; }
  .info-row .v { font-weight:600; color:#f3f4f6; }
  .cta { display:block; background:linear-gradient(135deg,#7c3aed,#8b5cf6); color:#fff; text-decoration:none; text-align:center; padding:14px; border-radius:10px; font-weight:700; font-size:15px; margin-top:24px; }
  .footer { text-align:center; padding:16px 28px 24px; font-size:12px; color:#6b7280; }
</style>
</head>
<body>
<div class="wrap">
  <div class="header">
    <h1>✅ Depósito confirmado!</h1>
    <p>Seu saldo foi creditado com sucesso.</p>
  </div>
  <div class="body">
    <div class="amount-box">
      <p class="label">Valor depositado</p>
      <p class="value">{{ $formattedAmount }}</p>
    </div>
    <div class="info-row"><span class="k">Titular</span><span class="v">{{ $user->name }}</span></div>
    <div class="info-row"><span class="k">ID da transação</span><span class="v">{{ $transactionId }}</span></div>
    <div class="info-row"><span class="k">Data/hora</span><span class="v">{{ $date }}</span></div>
    <div class="info-row"><span class="k">Método</span><span class="v">PIX</span></div>
    <a href="{{ config('app.url') }}" class="cta">Ir para minha carteira →</a>
  </div>
  <div class="footer">
    <p>ForaPix — Apostas Esportivas &bull; Não responda este e-mail.</p>
    <p>Problemas? Acesse <a href="{{ config('app.url') }}" style="color:#8b5cf6;">apostacasada.net</a></p>
  </div>
</div>
</body>
</html>

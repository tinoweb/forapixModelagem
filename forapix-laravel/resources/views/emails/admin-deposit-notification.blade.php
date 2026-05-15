<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Novo depósito recebido</title>
<style>
  body { margin:0; padding:0; background:#0a0b1a; font-family:'Segoe UI',Arial,sans-serif; color:#e5e7eb; }
  .wrap { max-width:560px; margin:32px auto; background:#111936; border-radius:16px; overflow:hidden; }
  .header { background:linear-gradient(135deg,#1e3a5f,#1a2d4a); padding:28px; border-bottom:1px solid rgba(255,255,255,.06); }
  .header h1 { margin:0 0 4px; font-size:20px; color:#fbbf24; }
  .header p  { margin:0; font-size:13px; color:#9ca3af; }
  .body { padding:28px; }
  .badge { display:inline-block; background:rgba(251,191,36,.15); color:#fbbf24; border:1px solid rgba(251,191,36,.3); border-radius:20px; padding:4px 12px; font-size:12px; font-weight:700; margin-bottom:20px; }
  .info-row { display:flex; justify-content:space-between; padding:10px 0; border-bottom:1px solid rgba(255,255,255,.06); font-size:14px; }
  .info-row:last-child { border-bottom:none; }
  .info-row .k { color:#9ca3af; }
  .info-row .v { font-weight:600; color:#f3f4f6; }
  .amount { font-size:28px; font-weight:700; color:#4ade80; margin:16px 0; }
  .cta { display:block; background:rgba(255,255,255,.06); border:1px solid rgba(255,255,255,.1); color:#e5e7eb; text-decoration:none; text-align:center; padding:12px; border-radius:10px; font-weight:600; font-size:14px; margin-top:20px; }
  .footer { text-align:center; padding:14px; font-size:11px; color:#4b5563; border-top:1px solid rgba(255,255,255,.04); }
</style>
</head>
<body>
<div class="wrap">
  <div class="header">
    <h1>💰 Novo depósito recebido</h1>
    <p>Um usuário realizou um depósito via PIX.</p>
  </div>
  <div class="body">
    <span class="badge">DEPÓSITO CONFIRMADO</span>
    <div class="amount">{{ $formattedAmount }}</div>
    <div class="info-row"><span class="k">Usuário</span><span class="v">{{ $user->name }}</span></div>
    <div class="info-row"><span class="k">Email</span><span class="v">{{ $user->email }}</span></div>
    <div class="info-row"><span class="k">ID da transação</span><span class="v">{{ $transactionId }}</span></div>
    <div class="info-row"><span class="k">Data/hora</span><span class="v">{{ $date }}</span></div>
    <div class="info-row"><span class="k">Método</span><span class="v">PIX (VeoPag)</span></div>
    <a href="{{ config('app.url') }}/admin" class="cta">Abrir painel administrativo →</a>
  </div>
  <div class="footer">ForaPix Admin — Notificação automática</div>
</div>
</body>
</html>

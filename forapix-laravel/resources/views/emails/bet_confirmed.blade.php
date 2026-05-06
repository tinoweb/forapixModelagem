<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Aposta confirmada</title>
<style>
  body { margin:0; padding:0; background:#0a0e1a; font-family:'Segoe UI',Arial,sans-serif; color:#e5e7eb; }
  .wrapper { max-width:560px; margin:0 auto; padding:32px 16px; }
  .card { background:#10152b; border-radius:16px; overflow:hidden; border:1px solid rgba(255,255,255,0.06); }
  .header { background:linear-gradient(135deg,#1e2a4a 0%,#0f172a 100%); padding:32px 28px 24px; text-align:center; }
  .logo { font-size:22px; font-weight:900; color:#f97316; letter-spacing:2px; margin-bottom:6px; }
  .header-title { font-size:18px; font-weight:700; color:#fff; }
  .header-sub { font-size:13px; color:#6b7280; margin-top:4px; }
  .body { padding:28px; }
  .greeting { font-size:15px; color:#d1d5db; margin-bottom:20px; }
  .section-title { font-size:10px; font-weight:800; letter-spacing:1.2px; text-transform:uppercase; color:#6b7280; margin-bottom:12px; }
  .detail-box { background:#0a0e1a; border-radius:12px; border:1px solid rgba(255,255,255,0.06); overflow:hidden; margin-bottom:20px; }
  .detail-row { display:flex; justify-content:space-between; align-items:center; padding:12px 16px; border-bottom:1px solid rgba(255,255,255,0.04); }
  .detail-row:last-child { border-bottom:none; }
  .detail-label { font-size:12px; color:#6b7280; }
  .detail-value { font-size:13px; font-weight:600; color:#fff; text-align:right; }
  .highlight { color:#f97316; }
  .badge { display:inline-block; padding:3px 12px; border-radius:999px; font-size:11px; font-weight:700; }
  .badge-pending { background:rgba(251,191,36,0.15); color:#fbbf24; border:1px solid rgba(251,191,36,0.3); }
  .potential-box { background:rgba(34,197,94,0.08); border:1px solid rgba(34,197,94,0.2); border-radius:12px; padding:16px 20px; text-align:center; margin-bottom:20px; }
  .potential-label { font-size:11px; color:#6b7280; text-transform:uppercase; letter-spacing:1px; margin-bottom:4px; }
  .potential-value { font-size:28px; font-weight:900; color:#22c55e; }
  .cta-btn { display:block; background:#f97316; color:#fff; text-decoration:none; text-align:center; font-size:14px; font-weight:800; letter-spacing:1px; padding:14px 24px; border-radius:12px; margin-bottom:20px; }
  .footer { padding:20px 28px; border-top:1px solid rgba(255,255,255,0.04); text-align:center; font-size:11px; color:#4b5563; }
  .footer a { color:#6b7280; text-decoration:none; }
</style>
</head>
<body>
<div class="wrapper">
  <div class="card">

    <div class="header">
      <div class="logo">APOSTACASADA</div>
      <div class="header-title">✅ Aposta confirmada!</div>
      <div class="header-sub">Sua aposta foi registrada com sucesso</div>
    </div>

    <div class="body">
      <p class="greeting">Olá, <strong>{{ $userName }}</strong>! Sua aposta na partida abaixo foi registrada e está pendente de resultado.</p>

      <div class="section-title">Partida</div>
      <div class="detail-box">
        <div class="detail-row">
          <span class="detail-label">Jogo</span>
          <span class="detail-value">{{ $gameName }}</span>
        </div>
        <div class="detail-row">
          <span class="detail-label">Adversários</span>
          <span class="detail-value">{{ $player1 }} vs {{ $player2 }}</span>
        </div>
        <div class="detail-row">
          <span class="detail-label">Data da partida</span>
          <span class="detail-value">{{ $matchDate }}</span>
        </div>
      </div>

      <div class="section-title">Detalhes da aposta</div>
      <div class="detail-box">
        <div class="detail-row">
          <span class="detail-label">Você apostou em</span>
          <span class="detail-value highlight">{{ $betLabel }}</span>
        </div>
        <div class="detail-row">
          <span class="detail-label">Valor apostado</span>
          <span class="detail-value">R$ {{ $amount }}</span>
        </div>
        <div class="detail-row">
          <span class="detail-label">Odds</span>
          <span class="detail-value">{{ $odds }}x</span>
        </div>
        <div class="detail-row">
          <span class="detail-label">Código da aposta</span>
          <span class="detail-value" style="font-size:11px;font-family:monospace;">{{ $betCode }}</span>
        </div>
        <div class="detail-row">
          <span class="detail-label">Status</span>
          <span class="detail-value"><span class="badge badge-pending">Pendente</span></span>
        </div>
      </div>

      <div class="potential-box">
        <div class="potential-label">Ganho potencial</div>
        <div class="potential-value">R$ {{ $potentialWin }}</div>
      </div>

      <a href="{{ $appUrl }}" class="cta-btn">Acompanhar partida →</a>

      <p style="font-size:12px;color:#6b7280;text-align:center;">
        O resultado será processado automaticamente ao fim da partida.<br>
        Em caso de cancelamento, o valor será reembolsado integralmente.
      </p>
    </div>

    <div class="footer">
      <p>© {{ date('Y') }} ApostaCasada · <a href="{{ $appUrl }}">apostacasada.net</a></p>
      <p style="margin-top:6px;">Você recebeu este email porque realizou uma aposta na plataforma.</p>
    </div>

  </div>
</div>
</body>
</html>

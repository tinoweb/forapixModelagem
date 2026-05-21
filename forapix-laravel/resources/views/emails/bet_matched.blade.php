<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Aposta casada</title>
<style>
  body { margin:0; padding:0; background:#0a0e1a; font-family:'Segoe UI',Arial,sans-serif; color:#e5e7eb; }
  .wrapper { max-width:560px; margin:0 auto; padding:32px 16px; }
  .card { background:#10152b; border-radius:16px; overflow:hidden; border:1px solid rgba(255,255,255,0.06); }
  .header { background:linear-gradient(135deg,#1e3a2a 0%,#0f172a 100%); padding:32px 28px 24px; text-align:center; }
  .logo { font-size:22px; font-weight:900; color:#f97316; letter-spacing:2px; margin-bottom:6px; }
  .header-title { font-size:20px; font-weight:700; color:#fff; }
  .header-sub { font-size:13px; color:#6b7280; margin-top:4px; }
  .body { padding:28px; }
  .greeting { font-size:15px; color:#d1d5db; margin-bottom:20px; }
  .section-title { font-size:10px; font-weight:800; letter-spacing:1.2px; text-transform:uppercase; color:#6b7280; margin-bottom:12px; }
  .detail-box { background:#0a0e1a; border-radius:12px; border:1px solid rgba(255,255,255,0.06); overflow:hidden; margin-bottom:20px; }
  .detail-row { display:flex; justify-content:space-between; align-items:center; padding:12px 16px; border-bottom:1px solid rgba(255,255,255,0.04); }
  .detail-row:last-child { border-bottom:none; }
  .detail-label { font-size:12px; color:#6b7280; }
  .detail-value { font-size:13px; font-weight:600; color:#fff; text-align:right; }
  .highlight-green { color:#22c55e; }
  .highlight-yellow { color:#fbbf24; }
  .highlight-orange { color:#f97316; }
  .badge { display:inline-block; padding:3px 12px; border-radius:999px; font-size:11px; font-weight:700; }
  .badge-confirmed { background:rgba(34,197,94,0.15); color:#22c55e; border:1px solid rgba(34,197,94,0.35); }
  .badge-partial   { background:rgba(99,102,241,0.15); color:#818cf8; border:1px solid rgba(99,102,241,0.35); }
  .summary-grid { display:grid; grid-template-columns:1fr 1fr; gap:12px; margin-bottom:20px; }
  .summary-box { background:#0a0e1a; border-radius:12px; border:1px solid rgba(255,255,255,0.06); padding:16px; text-align:center; }
  .summary-box-label { font-size:10px; text-transform:uppercase; letter-spacing:1px; color:#6b7280; margin-bottom:6px; }
  .summary-box-value { font-size:22px; font-weight:900; }
  .summary-box-value.green { color:#22c55e; }
  .summary-box-value.yellow { color:#fbbf24; }
  .alert-box { border-radius:12px; padding:14px 18px; margin-bottom:20px; font-size:13px; line-height:1.6; }
  .alert-partial { background:rgba(99,102,241,0.08); border:1px solid rgba(99,102,241,0.25); color:#a5b4fc; }
  .alert-full    { background:rgba(34,197,94,0.08);  border:1px solid rgba(34,197,94,0.25);  color:#86efac; }
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
      @if($isFullyMatched)
      <div class="header-title">🎉 Aposta 100% confirmada!</div>
      <div class="header-sub">Toda a sua aposta foi casada com sucesso</div>
      @else
      <div class="header-title">✅ Aposta parcialmente casada!</div>
      <div class="header-sub">Parte da sua aposta encontrou contraparte</div>
      @endif
    </div>

    <div class="body">
      <p class="greeting">Olá, <strong>{{ $userName }}</strong>!</p>

      @if($isFullyMatched)
      <div class="alert-box alert-full">
        <strong>✓ Confirmado!</strong> Sua aposta de <strong>R$ {{ $totalAmount }}</strong> em <strong>{{ $betLabel }}</strong>
        está totalmente casada. Agora é só torcer!
      </div>
      @else
      <div class="alert-box alert-partial">
        <strong>Casamento parcial.</strong> R$ {{ $matchedAmount }} da sua aposta foram confirmados.
        O restante (R$ {{ $unmatchedAmount }}) continua aguardando um apostador do lado oposto.
        Ele será devolvido automaticamente se não houver casamento até o fim da partida.
      </div>
      @endif

      <div class="summary-grid">
        <div class="summary-box">
          <div class="summary-box-label">✓ Casado</div>
          <div class="summary-box-value green">R$ {{ $matchedAmount }}</div>
        </div>
        <div class="summary-box">
          <div class="summary-box-label">⏳ Pendente</div>
          <div class="summary-box-value yellow">R$ {{ $unmatchedAmount }}</div>
        </div>
      </div>

      <div class="section-title">Detalhes da aposta</div>
      <div class="detail-box">
        <div class="detail-row">
          <span class="detail-label">Partida</span>
          <span class="detail-value">{{ $player1 }} vs {{ $player2 }}</span>
        </div>
        <div class="detail-row">
          <span class="detail-label">Você apostou em</span>
          <span class="detail-value highlight-orange">{{ $betLabel }}</span>
        </div>
        <div class="detail-row">
          <span class="detail-label">Valor total apostado</span>
          <span class="detail-value">R$ {{ $totalAmount }}</span>
        </div>
        <div class="detail-row">
          <span class="detail-label">Valor casado (em disputa)</span>
          <span class="detail-value highlight-green">R$ {{ $matchedAmount }}</span>
        </div>
        @if(!$isFullyMatched)
        <div class="detail-row">
          <span class="detail-label">Valor pendente (devolvido se não casar)</span>
          <span class="detail-value highlight-yellow">R$ {{ $unmatchedAmount }}</span>
        </div>
        @endif
        <div class="detail-row">
          <span class="detail-label">Status</span>
          <span class="detail-value">
            @if($isFullyMatched)
            <span class="badge badge-confirmed">✓ Confirmada</span>
            @else
            <span class="badge badge-partial">Parcial</span>
            @endif
          </span>
        </div>
        <div class="detail-row">
          <span class="detail-label">Código da aposta</span>
          <span class="detail-value" style="font-size:11px;font-family:monospace;">{{ $betCode }}</span>
        </div>
      </div>

      <a href="{{ $appUrl }}" class="cta-btn">Acompanhar partida →</a>

      <p style="font-size:12px;color:#6b7280;text-align:center;">
        O valor <strong style="color:#fbbf24;">não casado</strong> é sempre devolvido ao encerramento da partida,
        independente do resultado.<br>
        Apenas o valor <strong style="color:#22c55e;">casado</strong> participa do prêmio.
      </p>
    </div>

    <div class="footer">
      <p>© {{ date('Y') }} ApostaCasada · <a href="{{ $appUrl }}">apostacasada.net</a></p>
      <p style="margin-top:6px;">Você recebeu este email porque possui uma aposta ativa na plataforma.</p>
    </div>

  </div>
</div>
</body>
</html>

<?php
require __DIR__ . '/../app/bootstrap.php';
$user = require_client();
$uid = (int) $user['id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && post('action') === 'cancel') {
    if (client_cancel_request($uid, post_int('id'))) {
        flash('success', 'Votre demande est annulée.');
    } else {
        flash('error', 'Cette demande ne peut plus être annulée en ligne. Contactez la pension.');
    }
    redirect('/espace/reservations.php');
}

$requests = q_all('SELECT r.*, d.name AS dog FROM stay_requests r JOIN dogs d ON d.id = r.dog_id WHERE r.user_id = ? ORDER BY r.start_date DESC, r.id DESC LIMIT 200', [$uid]);
// Acomptes à verser : un bloc par demande (même communication = un seul virement)
$due = [];
foreach ($requests as $r) {
    if ($r['kind'] === 'sejour' && $r['pay_ref'] && in_array($r['status'], ['pending', 'confirmed'], true)
        && !$r['deposit_paid_at'] && !$r['paid_full_at'] && (float) $r['deposit_amount'] > 0 && $r['end_date'] >= today()) {
        $due[$r['pay_ref']][] = $r;
    }
}
$pay = load_payment();
$contracts = q_all('SELECT id, title, created_at FROM contracts WHERE user_id = ? ORDER BY created_at DESC', [$uid]);

page_start('Mes réservations', 'client', 'reservations');
?>
<p class="eyebrow">Suivi</p>
<h1>Mes réservations</h1>
<?php foreach ($due as $ref => $group):
    $deposit = array_sum(array_column($group, 'deposit_amount'));
    $total = array_sum(array_column($group, 'price_total')); ?>
<section class="card paybox">
  <div class="paybox__head">
    <span class="paybox__icon" aria-hidden="true"><?= icon('euro') ?></span>
    <div>
      <h2>Acompte à verser : <?= h(euros($deposit)) ?></h2>
      <p class="muted"><?= h(implode(' et ', array_unique(array_column($group, 'dog')))) ?> · <?= h(request_label($group[0])) ?> · prix estimé <?= h(euros($total)) ?></p>
    </div>
  </div>
  <p>Pour bloquer vos dates, faites un virement de <strong><?= h(euros($deposit)) ?></strong> (<?= h(rtrim(rtrim(number_format((float) $pay['pourcentage'], 2, ',', ''), '0'), ',')) ?> % du séjour) :</p>
  <dl class="paybox__facts">
    <div><dt>Compte</dt><dd><?= $pay['iban'] !== '' ? h(format_iban($pay['iban'])) : 'communiqué par la pension' ?></dd></div>
    <?php if ($pay['iban'] !== '' && $pay['titulaire'] !== ''): ?><div><dt>Au nom de</dt><dd><?= h($pay['titulaire']) ?></dd></div><?php endif; ?>
    <?php if ($pay['bic'] !== ''): ?><div><dt>BIC</dt><dd><?= h($pay['bic']) ?></dd></div><?php endif; ?>
    <div><dt>Communication</dt><dd class="paybox__ref"><?= h($ref) ?></dd></div>
  </dl>
  <p class="muted small">Recopiez bien la communication : elle permet à la pension de retrouver votre virement. Le solde est à régler à la pension. Ces informations vous ont aussi été envoyées par e-mail.</p>
</section>
<?php endforeach; ?>
<?php if (!$requests): ?>
  <div class="card empty"><p>Aucune demande pour l'instant.</p><a class="btn" href="/espace/demande.php">Demander un séjour</a></div>
<?php else: ?>
<div class="table-box">
  <table class="table">
    <thead><tr><th scope="col">Chien</th><th scope="col">Type</th><th scope="col">Dates</th><th scope="col">État</th><th scope="col">Paiement</th><th scope="col">Message de la pension</th><th scope="col"><span class="sr-only">Action</span></th></tr></thead>
    <tbody>
    <?php foreach ($requests as $r): ?>
      <tr>
        <td><strong><?= h($r['dog']) ?></strong></td>
        <td><?= $r['kind'] === 'journee' ? 'Journée (carte)' : 'Séjour' ?></td>
        <td><?= h(request_label($r)) ?></td>
        <td><?= status_badge($r['status']) ?></td>
        <td><?php [$pc, $pt] = payment_state($r); ?><?= in_array($r['status'], ['refused', 'cancelled'], true) && $pc === 'due' ? '—' : '<span class="paystate paystate--' . h($pc) . '">' . h($pt) . '</span>' ?></td>
        <td class="muted"><?= h($r['manager_message'] ?? '') ?: '—' ?></td>
        <td>
          <?php if ($r['status'] === 'pending'): ?>
          <form method="post" data-confirm="Annuler cette demande ?">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="cancel">
            <input type="hidden" name="id" value="<?= (int) $r['id'] ?>">
            <button type="submit" class="linklike">Annuler</button>
          </form>
          <?php endif; ?>
        </td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
</div>
<?php endif; ?>

<section class="section" id="contrats">
  <h2>Mes contrats</h2>
  <?php if (!$contracts): ?>
    <p class="muted">Aucun contrat pour l'instant. La pension les dépose ici après chaque séjour ou achat de carte.</p>
  <?php else: ?>
  <div class="docs">
    <?php foreach ($contracts as $c): ?>
    <div class="card doc">
      <span class="doc__icon" aria-hidden="true"><svg viewBox="0 0 24 24"><path d="M14 3H7a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V8z"/><path d="M14 3v5h5"/><path d="M9 13h6M9 17h4"/></svg></span>
      <div class="doc__text"><strong><?= h($c['title']) ?></strong><span>Ajouté le <?= h(date_fr(substr($c['created_at'], 0, 10))) ?> · PDF</span></div>
      <a href="/espace/contrat.php?id=<?= (int) $c['id'] ?>">Télécharger</a>
    </div>
    <?php endforeach; ?>
  </div>
  <p class="muted small">Les contrats sont déposés par la pension. Vous seul pouvez les ouvrir.</p>
  <?php endif; ?>
</section>
<?php page_end(['/js/espace.js']);

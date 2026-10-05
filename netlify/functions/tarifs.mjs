// GET /api/tarifs — tarifs de la pension (public, utilisé par le simulateur)
// PUT /api/tarifs — enregistre de nouveaux tarifs (administrateur connecté uniquement)
import { getStore } from '@netlify/blobs';
import { json, verifyRequest } from '../lib/auth.mjs';

const KEY = 'pension';
const store = () => getStore({ name: 'tarifs', consistency: 'strong' });

const isText = (v, max) => typeof v === 'string' && v.trim().length <= max;
const isPrice = (v) => typeof v === 'number' && Number.isFinite(v) && v > 0 && v <= 1000;

function clean(data) {
  if (!data || typeof data !== 'object') return null;
  const { seuilJours, gabarits } = data;
  if (!Number.isInteger(seuilJours) || seuilJours < 1 || seuilJours > 60) return null;
  if (!Array.isArray(gabarits) || gabarits.length < 1 || gabarits.length > 6) return null;
  const out = [];
  for (const g of gabarits) {
    if (!isText(g?.id, 30) || !isText(g?.nom, 40) || !g.nom.trim() || !isText(g?.description ?? '', 60)) return null;
    if (!isPrice(g.prix) || !isPrice(g.prixLongSejour)) return null;
    out.push({
      id: g.id.trim(),
      nom: g.nom.trim(),
      description: (g.description ?? '').trim(),
      prix: Math.round(g.prix * 100) / 100,
      prixLongSejour: Math.round(g.prixLongSejour * 100) / 100,
    });
  }
  return { seuilJours, gabarits: out };
}

export default async (req) => {
  if (req.method === 'GET') {
    // null = aucun tarif enregistré : le site utilise les valeurs par défaut de js/tarifs.js
    const tarifs = await store().get(KEY, { type: 'json' });
    return json({ tarifs: tarifs ?? null });
  }

  if (req.method === 'PUT') {
    if (!verifyRequest(req)) return json({ error: 'Session expirée, reconnectez-vous.' }, 401);
    let body;
    try {
      body = await req.json();
    } catch {
      return json({ error: 'Requête invalide.' }, 400);
    }
    const tarifs = clean(body);
    if (!tarifs) return json({ error: 'Les tarifs envoyés ne sont pas valides.' }, 400);
    await store().setJSON(KEY, { ...tarifs, misAJour: new Date().toISOString() });
    return json({ ok: true, tarifs });
  }

  return json({ error: 'Méthode non autorisée.' }, 405);
};

export const config = { path: '/api/tarifs' };

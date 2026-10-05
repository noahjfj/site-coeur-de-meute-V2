// POST /api/login — vérifie l'identifiant et le mot de passe, renvoie une session de 12 h.
import { credentials, createToken, json, safeEqual } from '../lib/auth.mjs';

const wait = (ms) => new Promise((resolve) => setTimeout(resolve, ms));

export default async (req) => {
  if (req.method !== 'POST') return json({ error: 'Méthode non autorisée.' }, 405);

  const creds = credentials();
  if (!creds) {
    return json({ error: "L'administration n'est pas encore configurée (identifiant et mot de passe à définir sur Netlify)." }, 503);
  }

  let body;
  try {
    body = await req.json();
  } catch {
    return json({ error: 'Requête invalide.' }, 400);
  }

  const userOk = safeEqual(body?.user ?? '', creds.user);
  const passOk = safeEqual(body?.password ?? '', creds.password);
  if (!userOk || !passOk) {
    await wait(1000); // ralentit les essais en série
    return json({ error: 'Identifiant ou mot de passe incorrect.' }, 401);
  }

  return json({ token: createToken(creds.user, creds.secret) });
};

export const config = { path: '/api/login' };

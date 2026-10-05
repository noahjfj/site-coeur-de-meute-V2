// Authentification de l'espace administrateur.
// Identifiant et mot de passe : variables d'environnement Netlify ADMIN_USER et ADMIN_PASSWORD.
import { createHash, createHmac, timingSafeEqual } from 'node:crypto';

const SESSION_HOURS = 12;

const sha256 = (value) => createHash('sha256').update(String(value)).digest();
const b64url = (buf) => Buffer.from(buf).toString('base64url');

// Comparaison à temps constant (évite de deviner le mot de passe caractère par caractère)
export const safeEqual = (a, b) => timingSafeEqual(sha256(a), sha256(b));

export function credentials() {
  const user = process.env.ADMIN_USER;
  const password = process.env.ADMIN_PASSWORD;
  if (!user || !password) return null;
  // Changer le mot de passe invalide automatiquement toutes les sessions ouvertes
  const secret = process.env.SESSION_SECRET || `coeur-de-meute:${password}`;
  return { user, password, secret };
}

export function createToken(user, secret) {
  const payload = b64url(JSON.stringify({ u: user, exp: Date.now() + SESSION_HOURS * 3600 * 1000 }));
  const sig = b64url(createHmac('sha256', secret).update(payload).digest());
  return `${payload}.${sig}`;
}

export function verifyRequest(req) {
  const creds = credentials();
  if (!creds) return false;
  const header = req.headers.get('authorization') || '';
  const token = header.startsWith('Bearer ') ? header.slice(7) : '';
  const [payload, sig] = token.split('.');
  if (!payload || !sig) return false;
  const expected = b64url(createHmac('sha256', creds.secret).update(payload).digest());
  if (!safeEqual(sig, expected)) return false;
  try {
    const { u, exp } = JSON.parse(Buffer.from(payload, 'base64url').toString('utf8'));
    return u === creds.user && typeof exp === 'number' && exp > Date.now();
  } catch {
    return false;
  }
}

export const json = (body, status = 200) =>
  new Response(JSON.stringify(body), {
    status,
    headers: { 'Content-Type': 'application/json; charset=utf-8', 'Cache-Control': 'no-store' },
  });

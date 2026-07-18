<?php
/**
 * Resolución de proxy confiable (helpers/proxy_confiable.php) — PURO, sin BD.
 * Contrato: X-Forwarded-* solo se honra si REMOTE_ADDR es un proxy confiable;
 * la IP real es el salto DERECHO no confiable de X-Forwarded-For.
 */

require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../../app/helpers/proxy_confiable.php';

echo "ProxyConfiableTest\n";

// ── Parseo de la lista ──
t_eq([], ms_proxy_lista_confiable(''), 'lista vacia = no confiar en nadie');
t_eq(['10.0.0.1', '172.16.0.0/12'], ms_proxy_lista_confiable(' 10.0.0.1 , 172.16.0.0/12 ,'), 'parseo con espacios y coma final');

// ── Match IP exacta y CIDR ──
t_ok(ms_ip_en_cidr('10.0.0.1', '10.0.0.1'), 'IP exacta coincide');
t_ok(!ms_ip_en_cidr('10.0.0.2', '10.0.0.1'), 'IP distinta no coincide');
t_ok(ms_ip_en_cidr('172.18.0.5', '172.16.0.0/12'), 'CIDR v4 dentro');
t_ok(!ms_ip_en_cidr('192.168.1.5', '172.16.0.0/12'), 'CIDR v4 fuera');
t_ok(ms_ip_en_cidr('172.16.0.1', '172.16.0.0/12'), 'borde inferior del CIDR');
t_ok(!ms_ip_en_cidr('172.32.0.0', '172.16.0.0/12'), 'borde superior fuera del CIDR');
t_ok(ms_ip_en_cidr('2001:db8::5', '2001:db8::/32'), 'CIDR v6 dentro');
t_ok(!ms_ip_en_cidr('2001:db9::5', '2001:db8::/32'), 'CIDR v6 fuera');
t_ok(!ms_ip_en_cidr('no-es-ip', '10.0.0.0/8'), 'basura no coincide');
t_ok(!ms_ip_en_cidr('10.0.0.1', '2001:db8::/32'), 'familias mezcladas no coinciden');

// ── Conexión directa: los headers se IGNORAN ──
$res = ms_proxy_resolver([
    'REMOTE_ADDR' => '187.150.10.20',
    'HTTP_X_FORWARDED_FOR' => '1.2.3.4',
    'HTTP_X_FORWARDED_PROTO' => 'https',
], ['172.18.0.0/16']);
t_eq(null, $res['ip'], 'directo: XFF falso ignorado');
t_eq(null, $res['https'], 'directo: proto falso ignorado');

// ── Sin lista de confiables: nunca cambia nada ──
$res = ms_proxy_resolver([
    'REMOTE_ADDR' => '172.18.0.9',
    'HTTP_X_FORWARDED_FOR' => '1.2.3.4',
    'HTTP_X_FORWARDED_PROTO' => 'https',
], []);
t_eq(null, $res['ip'], 'sin TRUSTED_PROXIES no se confia ni en el proxy');

// ── Detrás del proxy confiable: IP real y https ──
$res = ms_proxy_resolver([
    'REMOTE_ADDR' => '172.18.0.9',
    'HTTP_X_FORWARDED_FOR' => '187.150.10.20',
    'HTTP_X_FORWARDED_PROTO' => 'https',
], ['172.18.0.0/16']);
t_eq('187.150.10.20', $res['ip'], 'proxy confiable: IP real del cliente');
t_eq(true, $res['https'], 'proxy confiable: esquema https detectado');

// ── Cliente intenta spoofear su propio XFF: gana el salto derecho ──
$res = ms_proxy_resolver([
    'REMOTE_ADDR' => '172.18.0.9',
    'HTTP_X_FORWARDED_FOR' => '9.9.9.9, 187.150.10.20',
    'HTTP_X_FORWARDED_PROTO' => 'https',
], ['172.18.0.0/16']);
t_eq('187.150.10.20', $res['ip'], 'spoof a la izquierda: se toma el salto derecho');

// ── Cadena de dos proxies confiables ──
$res = ms_proxy_resolver([
    'REMOTE_ADDR' => '172.18.0.9',
    'HTTP_X_FORWARDED_FOR' => '187.150.10.20, 172.18.0.7',
    'HTTP_X_FORWARDED_PROTO' => 'https',
], ['172.18.0.0/16']);
t_eq('187.150.10.20', $res['ip'], 'dos proxies confiables: se salta al cliente');

// ── Toda la cadena es confiable: el cliente es el salto más lejano ──
$res = ms_proxy_resolver([
    'REMOTE_ADDR' => '172.18.0.9',
    'HTTP_X_FORWARDED_FOR' => '172.18.0.1',
    'HTTP_X_FORWARDED_PROTO' => 'https',
], ['172.18.0.0/16']);
t_eq('172.18.0.1', $res['ip'], 'cadena toda confiable: usa el salto mas lejano');

// ── Basura a la izquierda conserva el salto confiable derecho ──
$res = ms_proxy_resolver([
    'REMOTE_ADDR' => '172.18.0.9',
    'HTTP_X_FORWARDED_FOR' => 'basura, 172.18.0.1',
    'HTTP_X_FORWARDED_PROTO' => 'https',
], ['172.18.0.0/16']);
t_eq('172.18.0.1', $res['ip'], 'basura a la izquierda: conserva el confiable derecho');

// ── Proto http del proxy no fuerza https ──
$res = ms_proxy_resolver([
    'REMOTE_ADDR' => '172.18.0.9',
    'HTTP_X_FORWARDED_FOR' => '187.150.10.20',
    'HTTP_X_FORWARDED_PROTO' => 'http',
], ['172.18.0.0/16']);
t_eq(null, $res['https'], 'proto http: no se marca https');

t_fin();

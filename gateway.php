<?php
header('Content-Type: application/json');
session_start();
require 'vendor/autoload.php';

if (!class_exists('Google\Protobuf\RepeatedField', true)) {
    class_alias('Google\Protobuf\Internal\RepeatedField', 'Google\Protobuf\RepeatedField');
}
require_once __DIR__ . '/LunarisMeta/Authentication.php';
require_once __DIR__ . '/LunarisMeta/Tokenresp.php';
require_once __DIR__ . '/LunarisMeta/Accessrequest.php';

require_once __DIR__ . '/Lunaris/AuthenticationRequest.php';
require_once __DIR__ . '/Lunaris/AuthenticationResponse.php';
require_once __DIR__ . '/Lunaris/AccessRequest.php';
require_once __DIR__ . '/Lunaris/Sub2.php';
require_once __DIR__ . '/Lunaris/vg_version.php';

use Vanguard\AuthenticationRequest;
use Vanguard\AuthenticationResponse;
use Vanguard\AccessRequest;
use Vanguard\Sub2;
use Vanguard\vg_version;
use phpseclib3\Crypt\RSA;

$GAME_IDS = [
    "valo" => "com.riotgames.valorant",
    "league" => "com.riotgames.league",
];

function encode_varint(int $n): string
{
    $out = '';
    while (true) {
        $b = $n & 0x7F;
        $n >>= 7;
        if ($n) {
            $out .= chr($b | 0x80);
        } else {
            $out .= chr($b);
            break;
        }
    }
    return $out;
}

function fail(int $code, string $message): never
{
    http_response_code($code);
    die(json_encode(["success" => false, "message" => $message]));
}

function decrypt_resp(string $payload, string $privateKeyPem): string
{
    $minLength = 9 + 256 + 12 + 16;
    if (strlen($payload) < $minLength) {
        throw new \InvalidArgumentException('payload too short');
    }

    $offset = 9;
    $encryptedKey = substr($payload, $offset, 256);
    $offset += 256;
    $iv = substr($payload, $offset, 12);
    $offset += 12;
    $tag = substr($payload, -16);
    $ciphertext = substr($payload, $offset, strlen($payload) - $offset - 16);

    $rsa = RSA::loadPrivateKey($privateKeyPem)->withPadding(RSA::ENCRYPTION_OAEP)->withHash('sha512')->withMGFHash('sha512');
    $aesKey = $rsa->decrypt($encryptedKey);

    if ($aesKey === false || strlen($aesKey) !== 32) {
        throw new \RuntimeException('not lunaris generated session');
    }

    $plaintext = openssl_decrypt($ciphertext, 'aes-256-gcm', $aesKey, OPENSSL_RAW_DATA, $iv, $tag);

    if ($plaintext === false) {
        throw new \RuntimeException('failed to decrypt');
    }

    return $plaintext;
}

function build_payload(string $data, string $pubkey, string $type): string
{
    $key = random_bytes(32);
    $iv = random_bytes(12);
    $tag = '';

    $ciphertext = openssl_encrypt($data, 'aes-256-gcm', $key, OPENSSL_RAW_DATA, $iv, $tag, '', 16);

    $rsa = RSA::loadPublicKey($pubkey)->withPadding(RSA::ENCRYPTION_OAEP)->withHash('sha512')->withMGFHash('sha512');
    $rsaEncKey = $rsa->encrypt($key);


    $rito_payload = hex2bin("52470100") . $rsaEncKey . $iv . $ciphertext . $tag;
    $outerWrapper = "\x08" . $type . "\x12" . encode_varint(strlen($rito_payload));

    return $outerWrapper . $rito_payload;
}

$input = json_decode(file_get_contents("php://input"), true);
if (!is_array($input)) {
    fail(400, "request body must be valid json");
}

$action = isset($input["action"]) && is_string($input["action"]) ? $input["action"] : "auth";
$requested_game = isset($input["game"]) && is_string($input["game"]) ? $input["game"] : null;
$sid = isset($input["sid"]) && is_string($input["sid"]) ? $input["sid"] : null;
$version = isset($input["version"]) && is_string($input["version"]) ? $input["version"] : "10.0.26200.8037";
$gameToken = isset($input["gametoken"]) && is_string($input["gametoken"]) ? $input["gametoken"] : null;
$response_b64 = isset($input["response"]) && is_string($input["response"]) ? $input["response"] : null;


if ($action === "auth") {

    if (!$gameToken) {
        fail(400, "missing required field: gametoken");
    }

    if (!$requested_game) {
        fail(400, "missing required field: game");
    }

    if ($requested_game === "valo" && !$sid) {
        fail(400, "missing required field: sid for game=valo");
    }

    if (!isset($GAME_IDS[$requested_game])) {
        fail(400, "unknown game type: expected valo or league");
    }

    $gameId = $GAME_IDS[$requested_game];

    $sessionPrivKey = "-----BEGIN PRIVATE KEY-----\nMIIEvQIBADANBgkqhkiG9w0BAQEFAASCBKcwggSjAgEAAoIBAQCRwxFIp5KNjfxO\nUjjJOwU1GtHdCVxp5ww16a8BK6svYRnOQ9SgZAy7GptXipzzKmA49zdNgPqfQPy0\nrt8kI1spKnU2qlZsJFK7jwQudQXNGiFIzYrCqRT4FZ0kCIvm2Hc9kcM/A1NVpZRN\nuqUz4LkpyURLkfEW41F5sKB9bjBpAuUmfyMtfMquwlwSnOtHpRqyQFTbgLeiLCpa\n8JxBVSldCix0/f8Y4M/6AvB2Ty1Empkqe9ALIZoIBoz80xdIe8jz0urWBTLkGaSq\nm+OWC/CyYffxN+z39uDxDu4lxnN68rIemBdfHNvFT4t7RzwuLf3KxgQDm5lcH7SA\nsAaE97orAgMBAAECggEAFf99LvHbSZxmujXpIWApkQd91KtXqcPs29YV9fSZM9/7\np7LPoW/NXA96kjj2fDGmWUfgrhm2Y9fIS5x4oK4/+8kkl3NtnN8pJ+qVlE1PlXye\nuVYL3a+W3Tn7Koz/pX8rFRJX3RKraP7sOtmIRSQNIyP04qghMnXBaL7MpHa/Z5mF\n5q6iAhTQ7hC8/37lYxpU3IOYAp9kid1TL5jxGl0MnRB3taMc40H3zz6vWoUdrqVT\npjO44Hit0Vd1lBfaPDNDLISaqJh4pNUK1QPerVlot4aD7MVuME4+LPcSKuERMpe2\n/czjr+UfAQ16KZL4muRKfCIpDCP5K+4q7aaXbo0SQQKBgQDJD0KZV+gaNVbSy83d\nVTvhvqHpTCjGxYUr6P2Hs5JktPtffx8npSU9FjEMw8sXDOFWt1wGejXLqoES2kZy\nWJj/0J8NsH9N/GRrgDaDzusEKxDAQTCsgweyuDF72qBd6bgdesEjvVugi1oT2t5L\ni2lVdodaFbg9esUnh2z9qAKYbwKBgQC5l5FZBIu1KYTtAZ4hSkhM3BIe7GZRqpBL\nEcPaAdVchIS0F8GC38fZZfbIeMR6ei6sprMInK75PohEEX+kfK9his8u3wpQXz5g\nl7C4Bkfm6eZIg0R3UXsOmKs4redOsPR7pksBy6E75RWvoJ9FubHQSAu64imfhg4+\nHx1+V6FABQKBgA+AgrUKBxZGOIyGNDg1gylzpk4zlg1FW3A4RZv553amUZ9aUM+g\ntw9Pr7Z5PiZn0tP5zTmeoJk0a0FF3Rqh5CINNsY6boZXyHJVb16YTJyEqYT5QcR7\nUdOgygfvol+7TnvPZCbaSb9GH0ranDsc46E4Q1VZyVvMJHlYwrmIA7M3AoGBAKos\nK/3viRqn41ZeMVxc/EZdU9A++uLO/leXBnoTTEv0xqlLTwhtoJXaRJo1AhM7jSiK\nAYdYA6hOiiu/z8ZG/Zj26loT8SUY790pucRDHhLBTYIXuuOdNrR/kB/8dSdWYhYu\nuIAA8uo0d9n98YzBZCWSqg7F6Vx4Uv7rEOQT/arxAoGAKKWoX9ndR9+hgMGbGIF1\n1PNmJLKj3vI3pOzqN7gf31dZYyjYSnneUaVFSZ9zbSWzRIN5cSb7QDsLW+np+sDW\njEbwgwP08J51QJQc9OZzoudUIAQ+Fn19gQJYHZ8k/GDqumowV1Z77SDg9VQFocGT\n/l2kvfVWfjHoVqqWsXm+Dt0=\n-----END PRIVATE KEY-----\n";
    $_SESSION['private_key'] = $sessionPrivKey;
    $pubKeyBase64 = "MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEAz7Vh5LOgV9FxsyeXlvP6OIfD0BFDv65A4wG6pgKO5EbJ6zSxsnU/fkFJeSjE8hJxX2CeEV9XODahl2ofF/jfTv2GhQIJt7ePFT6s4M6ZmDiU/FC5nlJREA3FmQy7VYzPhCy0tLJOaFtZSgi3Scx2az5AJEPP/XKyphY0hF1UFw8dUgVa/NQvXZtgTtnt+8WRcBwDcryKsQIepK4u6xBLYdhR+U6zuQ3KcudI3/Ov4glRYem/XjtGBpGlPLdxbT60tPthcBcWDPWbza9FdrrhhRzNR3bFxreqQW2j1o+SW55+WoDJ5ZhLsdcoUkJL7Ecex+vrzJD3eI8fiEz2TaWOJwIDAQAB";

    $msg = new AuthenticationRequest();
    $msg->setMachineId(bin2hex(random_bytes(32)));

    $f2 = new Sub2();
    $f2->setA(1);
    $f2->setB(2);
    $f2->setVersion($version);
    $msg->setField2($f2);

    $msg->setGameToken($gameToken);

    if ($requested_game === "valo") {
        $msg->setExternalSid($sid);
    }

    $msg->setClientRsaPublicKey($pubKeyBase64 . "\n");
    $msg->setGameId($gameId);
    $msg->setBootState(3);

    $vg_ver = new vg_version();
    $vg_ver->setA(1);
    $vg_ver->setB(18);
    $vg_ver->setC(3);
    $vg_ver->setD(77);
    $msg->setVersion1($vg_ver);
    $msg->setVersion2($vg_ver);

    $publicKey = "-----BEGIN PUBLIC KEY-----\nMIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEAz7Vh5LOgV9FxsyeXlvP6O\nIfD0BFDv65A4wG6pgKO5EbJ6zSxsnU/fkFJeSjE8hJxX2CeEV9XODahl2ofF/jfTv\n2GhQIJt7ePFT6s4M6ZmDiU/FC5nlJREA3FmQy7VYzPhCy0tLJOaFtZSgi3Scx2az5\nAJEPP/XKyphY0hF1UFw8dUgVa/NQvXZtgTtnt+8WRcBwDcryKsQIepK4u6xBLYdhR\n+U6zuQ3KcudI3/Ov4glRYem/XjtGBpGlPLdxbT60tPthcBcWDPWbza9FdrrhhRzNR\n3bFxreqQW2j1o+SW55+WoDJ5ZhLsdcoUkJL7Ecex+vrzJD3eI8fiEz2TaWOJwIDAQ\nAB\n-----END PUBLIC KEY-----\n";

    $finalPayload = build_payload($msg->serializeToString(), $publicKey, "\x03");

    die(json_encode(["success" => true, "session_id" => session_id(), "data" => base64_encode($finalPayload)]));

} elseif ($action === "access" || $action === "heartbeat") {
    if (!$response_b64) {
        fail(400, "missing required field: response");
    }

    $responseBytes = base64_decode($response_b64, true);
    if ($responseBytes === false || strlen($responseBytes) === 0) {
        fail(400, "invalid response encoding");
    }

    $sessionPrivKey = $_SESSION['private_key'] ?? null;
    if (!$sessionPrivKey) {
        fail(400, "no active session -- call auth first");
    }

    try {
        $decrypted = decrypt_resp($responseBytes, $sessionPrivKey);
    } catch (\InvalidArgumentException $e) {
        fail(400, "not lunaris generated session");
    } catch (\RuntimeException $e) {
        fail(400, "not lunaris generated session");
    }

    $msg = new AuthenticationResponse();
    $msg->mergeFromString($decrypted);

    $serverPublicKey = $msg->getServerRsaPublicKey();
    if (!$serverPublicKey) {
        fail(400, "broken resp / api needs update");
    }

    $access = new AccessRequest();
    $access->setToken($msg->getToken());

    $type = $action === "access" ? "\x04" : "\x07";
    $finalPayload = build_payload($access->serializeToString(), $serverPublicKey, $type);

    die(json_encode(["success" => true, "data" => base64_encode($finalPayload)]));

} elseif ($action === "refresh") {
    $session_id = isset($input["session_id"]) && is_string($input["session_id"]) ? $input["session_id"] : null;
    $token = isset($input["token"]) && is_string($input["token"]) ? $input["token"] : null;
    $rsid = isset($input["sid"]) && is_string($input["sid"]) ? $input["sid"] : null;
    $game = isset($input["game"]) && is_string($input["game"]) ? $input["game"] : "valo";
    $region = isset($input["region"]) && is_string($input["region"]) ? $input["region"] : "eu";

    if (!$session_id || !$token || !$rsid) {
        fail(400, "missing required fields: session_id, token, sid");
    }

    $ticketDir = __DIR__ . "/tickets";
    if (!is_dir($ticketDir)) {
        mkdir($ticketDir, 0777, true);
    }

    $entry = [
        "session_id" => $session_id,
        "token" => $token,
        "sid" => $rsid,
        "game" => $game,
        "region" => $region,
        "status" => "pending",
        "created_at" => time()
    ];

    file_put_contents(
        $ticketDir . "/" . preg_replace('/[^a-zA-Z0-9_-]/', '_', $session_id) . ".json",
        json_encode($entry)
    );

    die(json_encode(["success" => true, "session_id" => $session_id]));

} elseif ($action === "poll") {
    $session_id = isset($input["session_id"]) && is_string($input["session_id"]) ? $input["session_id"] : null;

    if (!$session_id) {
        fail(400, "missing required field: session_id");
    }

    $ticketFile = __DIR__ . "/tickets/" . preg_replace('/[^a-zA-Z0-9_-]/', '_', $session_id) . ".json";

    if (!file_exists($ticketFile)) {
        fail(404, "session not found");
    }

    $entry = json_decode(file_get_contents($ticketFile), true);

    $status = isset($entry["status"]) ? $entry["status"] : "pending";

    if ($status === "ready" && isset($entry["ticket"])) {
        die(json_encode(["status" => "ready", "ticket" => $entry["ticket"]]));
    } elseif ($status === "failed") {
        $error = isset($entry["error"]) ? $entry["error"] : "unknown error";
        die(json_encode(["status" => "failed", "error" => $error]));
    } else {
        die(json_encode(["status" => "pending"]));
    }

} else {
    fail(400, "unknown action");
}
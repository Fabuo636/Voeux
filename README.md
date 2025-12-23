# Documentation API SMS - Zen Apps

## Endpoint

```
POST https://advancedsmssending.zen-apps.com/sms_gateway.php
```

## Authentification

L'API utilise une clé API passée dans le header HTTP.

| Header | Valeur |
|--------|--------|
| `Content-Type` | `application/json` |
| `X-API-KEY` | `votre_cle_api` |

---

## Paramètres du Body (JSON)

| Paramètre | Type | Obligatoire | Description |
|-----------|------|-------------|-------------|
| `senderid` | string | ✅ Oui | Nom de l'expéditeur affiché (ex: `MUTZIG`) |
| `sms` | string | ✅ Oui | Contenu du message SMS |
| `mobiles` | string | ✅ Oui | Numéro(s) de téléphone séparés par des virgules (format international recommandé, ex: `237690689765`) |
| `scheduletime` | string | ❌ Non | Date/heure d'envoi programmé. Laisser vide `""` pour envoi immédiat |

---

## Exemples d'appel

### cURL (Linux/Mac)

```bash
curl -X POST "https://advancedsmssending.zen-apps.com/sms_gateway.php" \
  -H "Content-Type: application/json" \
  -H "X-API-KEY: votre_cle_api" \
  -d '{
    "senderid": "MUTZIG",
    "sms": "Bonjour, ceci est un test SMS",
    "mobiles": "237690689765",
    "scheduletime": ""
  }'
```

### cURL (Windows PowerShell)

```powershell
$body = '{"senderid":"MUTZIG","sms":"Bonjour, ceci est un test SMS","mobiles":"237690689765","scheduletime":""}'

curl.exe -X POST "https://advancedsmssending.zen-apps.com/sms_gateway.php" `
  -H "Content-Type: application/json" `
  -H "X-API-KEY: votre_cle_api" `
  -d $body
```

### JavaScript (Fetch)

```javascript
const sendSMS = async () => {
  const response = await fetch('https://advancedsmssending.zen-apps.com/sms_gateway.php', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      'X-API-KEY': 'votre_cle_api'
    },
    body: JSON.stringify({
      senderid: 'MUTZIG',
      sms: 'Bonjour, ceci est un test SMS',
      mobiles: '237690689765',
      scheduletime: ''
    })
  });

  const data = await response.json();
  console.log(data);
};

sendSMS();
```

### PHP (cURL)

```php
<?php
$url = 'https://advancedsmssending.zen-apps.com/sms_gateway.php';
$apiKey = 'votre_cle_api';

$data = [
    'senderid' => 'MUTZIG',
    'sms' => 'Bonjour, ceci est un test SMS',
    'mobiles' => '237690689765',
    'scheduletime' => ''
];

$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'X-API-KEY: ' . $apiKey
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "HTTP Code: $httpCode\n";
echo "Response: $response\n";
?>
```

### Python (requests)

```python
import requests

url = 'https://advancedsmssending.zen-apps.com/sms_gateway.php'
headers = {
    'Content-Type': 'application/json',
    'X-API-KEY': 'votre_cle_api'
}
payload = {
    'senderid': 'MUTZIG',
    'sms': 'Bonjour, ceci est un test SMS',
    'mobiles': '237690689765',
    'scheduletime': ''
}

response = requests.post(url, json=payload, headers=headers)
print(f'Status: {response.status_code}')
print(f'Response: {response.json()}')
```

---

## Envoi à plusieurs destinataires

Pour envoyer à plusieurs numéros, séparez-les par des virgules :

```json
{
  "senderid": "MUTZIG",
  "sms": "Message groupé",
  "mobiles": "237690689765,237681638178,237699999999",
  "scheduletime": ""
}
```

---

## Réponse de l'API

### Succès (HTTP 200)

```json
{
  "success": true,
  "provider_http_code": 200,
  "provider_response": {
    "responsecode": 1,
    "responsedescription": "success",
    "responsemessage": "success",
    "sms": [...]
  }
}
```

### Erreur - JSON invalide

```json
{
  "error": "Body JSON invalide."
}
```

### Erreur - Clé API invalide (HTTP 401/403)

```json
{
  "error": "Unauthorized"
}
```

---

## Codes de réponse

| Code | Signification |
|------|---------------|
| `responsecode: 1` | SMS envoyé avec succès |
| `responsecode: 0` | Échec de l'envoi |

---

## Bonnes pratiques

1. **Format des numéros** : Utilisez le format international avec indicatif pays (ex: `237` pour le Cameroun)
2. **Sécurité** : Ne jamais exposer la clé API dans le code frontend ou les dépôts publics
3. **Gestion d'erreurs** : Toujours vérifier le `responsecode` dans la réponse
4. **Longueur SMS** : Un SMS standard = 160 caractères. Au-delà, le message sera découpé en plusieurs SMS

---

## Support

En cas de problème :
- Vérifiez que la clé API est valide
- Vérifiez le format JSON (guillemets doubles, pas de virgule finale)
- Vérifiez le format des numéros de téléphone

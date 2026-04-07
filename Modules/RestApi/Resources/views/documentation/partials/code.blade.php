@php
    use Illuminate\Support\Str;

    $fullUrl = ($baseUrl ?? url('/api/application-integration')) . $path;
    $jsonBody = $body !== null ? json_encode($body, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) : null;
    $jsonResponse = $response !== null ? json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) : null;
    $id = 'code-' . Str::random(6);

    $methodLower = strtolower($method);
    $jsBody = $jsonBody ? "\n  ,body: JSON.stringify($jsonBody)" : '';
    $pyBody = $jsonBody ? "payload = $jsonBody\nresponse = requests.request(\"$method\", url, headers=headers, json=payload)" : "payload = None\nresponse = requests.request(\"$method\", url, headers=headers)";
    $phpBody = $jsonBody ? "'json' => $jsonBody," : "'json' => new stdClass(),";
    $javaBody = $jsonBody
        ? ".method(\"$method\", HttpRequest.BodyPublishers.ofString(\"" . addslashes($jsonBody) . "\"))"
        : ".method(\"$method\", HttpRequest.BodyPublishers.noBody())";
    $goInit = $jsonBody ? "body := []byte(`$jsonBody`)" : "var body []byte";
    $goBuffer = "bytes.NewBuffer(body)";
    $dartBody = $jsonBody ? "    body: jsonEncode($jsonBody),\n" : "";

    $samples = [
        'js' => <<<CODE
const res = await fetch('$fullUrl', {
  method: '$method',
  headers: {
    'Authorization': 'Bearer <TOKEN>',
    'Accept': 'application/json',
    'Content-Type': 'application/json',
  }{$jsBody}
});
const data = await res.json();
console.log(data);
CODE,
        'python' => <<<CODE
import requests, json

url = "$fullUrl"
headers = {
  "Authorization": "Bearer <TOKEN>",
  "Accept": "application/json",
  "Content-Type": "application/json",
}
{$pyBody}
print(response.json())
CODE,
        'php' => <<<CODE
\$client = new \\GuzzleHttp\\Client();
\$response = \$client->request('$method', '$fullUrl', [
    'headers' => [
        'Authorization' => 'Bearer <TOKEN>',
        'Accept' => 'application/json',
        'Content-Type' => 'application/json',
    ],
    {$phpBody}
]);
echo \$response->getBody();
CODE,
        'java' => <<<CODE
HttpClient client = HttpClient.newHttpClient();
HttpRequest request = HttpRequest.newBuilder()
    .uri(URI.create("$fullUrl"))
    .header("Authorization", "Bearer <TOKEN>")
    .header("Content-Type", "application/json")
    {$javaBody}
    .build();
HttpResponse<String> response = client.send(request, HttpResponse.BodyHandlers.ofString());
System.out.println(response.body());
CODE,
        'go' => <<<CODE
package main

import (
  "bytes"
  "fmt"
  "net/http"
)

func main() {
  $goInit
  req, _ := http.NewRequest("$method", "$fullUrl", $goBuffer)
  req.Header.Set("Authorization", "Bearer <TOKEN>")
  req.Header.Set("Content-Type", "application/json")

  res, _ := http.DefaultClient.Do(req)
  defer res.Body.Close()
  fmt.Println(res.Status)
}
CODE,
        'dart' => <<<CODE
import 'package:http/http.dart' as http;
import 'dart:convert';

void main() async {
  final url = Uri.parse('$fullUrl');
  final res = await http.{$methodLower}(
    url,
    headers: {
      'Authorization': 'Bearer <TOKEN>',
      'Content-Type': 'application/json',
    },
{$dartBody}
  );
  print(res.statusCode);
  print(res.body);
}
CODE,
    ];
@endphp

<div class="code-shell">
    <div class="code-tabs">
        @foreach($samples as $lang => $code)
            <button class="code-tab {{ $loop->first ? 'active' : '' }}" data-lang="{{ $lang }}">{{ strtoupper($lang) }}</button>
        @endforeach
    </div>
    <div class="code-body">
        <button class="code-copy">Copy</button>
        @foreach($samples as $lang => $code)
            <pre id="{{ $loop->first ? $id : $id . '-' . $lang }}" data-lang="{{ $lang }}" style="{{ $loop->first ? '' : 'display:none;' }}">{{ $code }}
@if($jsonResponse && $loop->first)

// Response
{{ $jsonResponse }}
@endif
</pre>
        @endforeach
    </div>
</div>


$ErrorActionPreference = 'Stop'
$posts = Get-Content -LiteralPath (Join-Path $PSScriptRoot 'legacy-wordpress-posts.json') -Raw | ConvertFrom-Json
$destination = Join-Path $PSScriptRoot 'assets/legacy-articles'
New-Item -ItemType Directory -Path $destination -Force | Out-Null
$urls = [System.Collections.Generic.HashSet[string]]::new()

foreach ($post in $posts) {
    foreach ($match in [regex]::Matches($post.content.rendered, '<img[^>]*?\bsrc="([^"]+)"')) {
        $url = [System.Net.WebUtility]::HtmlDecode($match.Groups[1].Value)
        if ($url.StartsWith('https://e4engineers.in/wp-content/uploads/')) {
            [void]$urls.Add($url)
        }
    }
    $featured = $post.'_embedded'.'wp:featuredmedia' | Select-Object -First 1 -ExpandProperty source_url -ErrorAction SilentlyContinue
    if ($featured -and $featured.StartsWith('https://e4engineers.in/wp-content/uploads/')) {
        [void]$urls.Add($featured)
    }
}

$urls | ForEach-Object -Parallel {
    $uri = [uri]$_
    $extension = [IO.Path]::GetExtension($uri.AbsolutePath).ToLowerInvariant()
    $sha1 = [Security.Cryptography.SHA1]::HashData([Text.Encoding]::UTF8.GetBytes($_))
    $name = ([Convert]::ToHexString($sha1)).ToLowerInvariant() + $extension
    $path = Join-Path $using:destination $name
    if (-not (Test-Path -LiteralPath $path)) {
        & curl.exe --http1.1 --location --silent --show-error --fail --retry 2 --max-time 45 --output $path $_
        if ($LASTEXITCODE -ne 0) {
            throw "Failed to download $_"
        }
    }
    [pscustomobject]@{ Name = $name; Bytes = (Get-Item -LiteralPath $path).Length }
} -ThrottleLimit 6

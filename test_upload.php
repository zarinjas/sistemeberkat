<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

$user = \App\Models\User::where('role', 'superadmin')->first();
if (!$user) die("No superadmin found\n");

// Create a dummy image
$imagePath = __DIR__.'/storage/app/public/test.jpg';
file_put_contents($imagePath, base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNkYAAAAAYAAjCB0C8AAAAASUVORK5CYII='));
$file = new \Illuminate\Http\UploadedFile($imagePath, 'test.jpg', 'image/jpeg', null, true);

$request = \Illuminate\Http\Request::create('/admin/system/dashboard-posters', 'POST', [
    'title' => 'Test Poster'
], [], ['image' => $file]);

$request->setUserResolver(fn() => $user);

// handle request
$response = $kernel->handle($request);
echo "Status: " . $response->getStatusCode() . "\n";
if ($response->getStatusCode() == 302) {
    echo "Redirect: " . $response->headers->get('Location') . "\n";
    $session = $request->getSession();
    if ($session && $session->has('errors')) {
        echo "Errors: " . json_encode($session->get('errors')->getBag('default')->getMessages()) . "\n";
    }
} else {
    echo $response->getContent() . "\n";
}

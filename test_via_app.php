<?php
$app = require __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

$user = \App\Models\User::where('role', 'superadmin')->first();

// Create fake request
$imagePath = __DIR__.'/storage/framework/testing/test.png';
@mkdir(dirname($imagePath), 0777, true);
file_put_contents($imagePath, base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNkYAAAAAYAAjCB0C8AAAAASUVORK5CYII='));
$file = new \Illuminate\Http\UploadedFile($imagePath, 'test.png', 'image/png', null, true);

$request = \Illuminate\Http\Request::create('/admin/system/dashboard-posters', 'POST', [
    'title' => 'Test Poster'
], [], ['image' => $file]);

$request->setUserResolver(fn() => $user);

$response = $kernel->handle($request);
echo "Status: " . $response->getStatusCode() . "\n";
if ($response->getStatusCode() === 302) {
    echo "Redirect Location: " . $response->headers->get('Location') . "\n";
    $session = $request->getSession();
    if ($session && $session->has('errors')) {
        echo "Errors: " . json_encode($session->get('errors')->getBag('default')->getMessages()) . "\n";
    }
} else {
    echo "Body: " . substr($response->getContent(), 0, 500) . "\n";
}

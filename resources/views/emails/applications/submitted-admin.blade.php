<!doctype html>
<html lang="ms">
<head>
    <meta charset="utf-8">
    <title>Permohonan Baru Dihantar</title>
</head>
<body style="margin:0; background:#f8fafc; font-family:Arial, sans-serif; color:#0f172a;">
    <div style="max-width:640px; margin:0 auto; padding:24px;">
        <div style="background:#ffffff; border:1px solid #e2e8f0; border-radius:20px; overflow:hidden;">
            <div style="background:linear-gradient(135deg, #0f172a, #1e3a8a); padding:24px; color:#ffffff;">
                <p style="margin:0 0 8px; font-size:12px; letter-spacing:.16em; text-transform:uppercase; opacity:.8;">Notifikasi Pentadbiran e-BERKAT</p>
                <h1 style="margin:0; font-size:24px; line-height:1.3;">Permohonan baharu memerlukan semakan admin</h1>
            </div>
            <div style="padding:24px;">
                <p style="margin:0 0 16px; font-size:14px; line-height:1.7;">Satu permohonan baharu telah berjaya dihantar melalui sistem e-BERKAT. Emel ini dihantar sebagai pemakluman rasmi kepada pihak admin untuk tindakan semakan lanjut.</p>
                <table style="width:100%; border-collapse:collapse; margin-bottom:20px;">
                    <tr>
                        <td style="padding:10px 0; border-bottom:1px solid #e2e8f0; font-size:13px; color:#475569; width:38%;">Tajuk Borang</td>
                        <td style="padding:10px 0; border-bottom:1px solid #e2e8f0; font-size:13px; font-weight:700;">{{ $formTitle }}</td>
                    </tr>
                    <tr>
                        <td style="padding:10px 0; border-bottom:1px solid #e2e8f0; font-size:13px; color:#475569;">No. Rujukan</td>
                        <td style="padding:10px 0; border-bottom:1px solid #e2e8f0; font-size:13px; font-weight:700;">{{ $application->reference_no ?: 'APP-'.$application->id }}</td>
                    </tr>
                    <tr>
                        <td style="padding:10px 0; border-bottom:1px solid #e2e8f0; font-size:13px; color:#475569;">Nama Pemohon</td>
                        <td style="padding:10px 0; border-bottom:1px solid #e2e8f0; font-size:13px; font-weight:700;">{{ $application->user?->name ?: '-' }}</td>
                    </tr>
                    <tr>
                        <td style="padding:10px 0; border-bottom:1px solid #e2e8f0; font-size:13px; color:#475569;">Email Pemohon</td>
                        <td style="padding:10px 0; border-bottom:1px solid #e2e8f0; font-size:13px; font-weight:700;">{{ $application->user?->email ?: '-' }}</td>
                    </tr>
                    <tr>
                        <td style="padding:10px 0; border-bottom:1px solid #e2e8f0; font-size:13px; color:#475569;">Tarikh Hantar</td>
                        <td style="padding:10px 0; border-bottom:1px solid #e2e8f0; font-size:13px; font-weight:700;">{{ $submittedAtLabel }}</td>
                    </tr>
                    <tr>
                        <td style="padding:10px 0; font-size:13px; color:#475569;">Jumlah Dipohon</td>
                        <td style="padding:10px 0; font-size:13px; font-weight:700;">{{ $amountLabel }}</td>
                    </tr>
                </table>
                <a href="{{ $reviewUrl }}" style="display:inline-block; padding:12px 18px; border-radius:12px; background:#1d4ed8; color:#ffffff; text-decoration:none; font-size:13px; font-weight:700;">Buka Modul Semakan</a>
                <p style="margin:20px 0 0; font-size:12px; line-height:1.7; color:#64748b;">Sila gunakan no. rujukan permohonan di atas sebagai rujukan rasmi dalam semakan dalaman. Emel ini dihantar secara automatik berdasarkan alamat emel akaun admin yang berdaftar dalam sistem.</p>
            </div>
        </div>
    </div>
</body>
</html>
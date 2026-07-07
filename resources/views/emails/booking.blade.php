@php
    use App\Support\Settings;

    $siteName = config('app.name', 'NOCTIS');
    $accent = '#4d8bc4';
    $adminEmail = (string) Settings::get('admin_notify_email', '');
    $dateFmt = (string) Settings::get('date_format', 'd/m/Y');
    $timeFmt = (string) Settings::get('time_format', 'H:i');
    $dateStr = $booking->ride_date ? $booking->ride_date->format($dateFmt) : '';
    $timeStr = $booking->ride_time ? date($timeFmt, strtotime($booking->ride_time)) : '';

    $rows = [
        __('Référence') => $booking->booking_ref,
        __('Départ') => $booking->pickup_address,
        __('Arrivée') => $booking->dropoff_address,
        __('Date') => $dateStr.($timeStr ? ' à '.$timeStr : ''),
        __('Véhicule') => $booking->vehicle_name,
        __('Distance') => $booking->distance_km.' km',
        __('Total payé') => Settings::formatPrice($booking->price),
    ];
@endphp
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>{{ $siteName }}</title>
</head>
<body style="margin:0;padding:0;background-color:#f0f2f5;font-family:'Segoe UI',Arial,Helvetica,sans-serif;">
<table width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color:#f0f2f5;">
<tr><td align="center" style="padding:32px 16px;">

  <!-- Carte principale -->
  <table width="600" cellpadding="0" cellspacing="0" border="0" style="max-width:600px;width:100%;background:#ffffff;border-radius:10px;overflow:hidden;box-shadow:0 2px 12px rgba(0,0,0,.08);">

    <!-- En-tête NOCTIS -->
    <tr>
      <td style="background:#0a0c12;padding:28px 32px;text-align:center;">
        <span style="font-family:'Georgia',serif;font-size:26px;font-weight:400;letter-spacing:6px;color:#ffffff;text-transform:uppercase;">NOCTIS</span>
        <div style="margin-top:6px;font-size:11px;letter-spacing:3px;color:{{ $accent }};text-transform:uppercase;">Chauffeur Privé</div>
      </td>
    </tr>

    <!-- Corps -->
    <tr>
      <td style="padding:32px 32px 8px 32px;">
        @foreach ($paragraphs as $p)
            @if (trim($p) !== '')
                <p style="margin:0 0 14px 0;color:#444444;font-size:15px;line-height:1.6;">{!! nl2br(e(trim($p))) !!}</p>
            @endif
        @endforeach

        <table width="100%" cellpadding="0" cellspacing="0" border="0" style="border-collapse:collapse;border:1px solid #e0e0e0;border-radius:6px;overflow:hidden;margin:20px 0;">
          <tbody>
            @foreach ($rows as $label => $value)
              <tr style="background:{{ $loop->odd ? '#ffffff' : '#f9f9f9' }};">
                <td style="padding:10px 14px;color:#888888;font-size:13px;white-space:nowrap;border-bottom:1px solid #eeeeee;">{{ $label }}</td>
                <td style="padding:10px 14px;color:#222222;font-size:13px;border-bottom:1px solid #eeeeee;">
                  @if ($label === __('Total payé'))<strong>{{ $value }}</strong>@else{{ $value }}@endif
                </td>
              </tr>
            @endforeach
          </tbody>
        </table>
      </td>
    </tr>

    <!-- Pied de page -->
    <tr>
      <td style="background:#f7f7f7;padding:20px 32px;border-top:1px solid #e8e8e8;text-align:center;">
        <p style="margin:0 0 6px 0;font-size:12px;color:#999999;">{{ $siteName }} · {{ __('Chauffeur Privé') }}</p>
        @if ($adminEmail !== '')
        <p style="margin:0;font-size:12px;color:#bbbbbb;">
          <a href="mailto:{{ $adminEmail }}" style="color:{{ $accent }};text-decoration:none;">{{ $adminEmail }}</a>
        </p>
        @endif
      </td>
    </tr>

  </table>

</td></tr>
</table>
</body>
</html>
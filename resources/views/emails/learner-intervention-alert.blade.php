<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Learner intervention alerts</title>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; max-width: 720px; margin: 0 auto; padding: 20px; background: #f4f4f4; }
        .container { background: #fff; border-radius: 10px; padding: 28px; box-shadow: 0 2px 4px rgba(0,0,0,0.08); }
        .header { background: linear-gradient(135deg, #3E6866 0%, #50C1AE 100%); color: #fff; padding: 24px; border-radius: 10px 10px 0 0; margin: -28px -28px 24px -28px; }
        .alert { border: 1px solid #e5e7eb; border-left: 4px solid #50C1AE; border-radius: 6px; padding: 14px 16px; margin: 12px 0; background: #f8fafc; }
        .alert.inactive { border-left-color: #d97706; }
        .alert.quiz { border-left-color: #dc2626; }
        .label { font-size: 11px; text-transform: uppercase; letter-spacing: 0.04em; color: #64748b; }
        .footer { text-align: center; color: #666; font-size: 12px; margin-top: 28px; padding-top: 16px; border-top: 1px solid #ddd; }
        table { width: 100%; border-collapse: collapse; font-size: 14px; }
        td { padding: 4px 0; vertical-align: top; }
        td.k { width: 140px; color: #64748b; }
    </style>
</head>
<body>
<div class="container">
    <div class="header">
        <h1 style="margin:0;font-size:22px;">Learner intervention alerts</h1>
        <p style="margin:8px 0 0;opacity:0.9;">Nightly check · {{ $generatedAt->format('d M Y H:i') }}</p>
    </div>

    <p>The following learners need facilitator follow-up (inactivity ≥ 7 days, or the same quiz failed twice without a pass).</p>

    @foreach($alerts as $alert)
        @php
            $isQuiz = $alert->reason === 'quiz_failed_twice';
            $reasonLabel = $isQuiz ? 'Failed same quiz twice' : 'Inactive 7+ days';
        @endphp
        <div class="alert {{ $isQuiz ? 'quiz' : 'inactive' }}">
            <div class="label">{{ $reasonLabel }}</div>
            <table>
                <tr>
                    <td class="k">Learner</td>
                    <td><strong>{{ $alert->learner_name }}</strong>@if(!empty($alert->payload['email'])) ({{ $alert->payload['email'] }})@endif</td>
                </tr>
                <tr>
                    <td class="k">Last login</td>
                    <td>{{ $alert->last_login_at ? $alert->last_login_at->format('d M Y H:i') : 'Never / unknown' }}</td>
                </tr>
                <tr>
                    <td class="k">{{ $isQuiz ? 'Stuck quiz' : 'Stuck module' }}</td>
                    <td>{{ $alert->stuck_label ?: '—' }}</td>
                </tr>
                @if(!empty($alert->payload['fail_count']))
                    <tr>
                        <td class="k">Failed attempts</td>
                        <td>{{ $alert->payload['fail_count'] }}</td>
                    </tr>
                @endif
                @if(!empty($alert->payload['last_active_date']))
                    <tr>
                        <td class="k">Last active date</td>
                        <td>{{ $alert->payload['last_active_date'] }}</td>
                    </tr>
                @endif
                <tr>
                    <td class="k">Facilitator ID</td>
                    <td>{{ $alert->facilitator_id ?: 'Unassigned' }}</td>
                </tr>
            </table>
        </div>
    @endforeach

    <div class="footer">
        <p>Sent automatically to admin@agrisiti.com · Agrisiti Academy</p>
    </div>
</div>
</body>
</html>

<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>{{ ucfirst($section) }} Report</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; }
        h1 { font-size: 20px; margin-bottom: 8px; }
        h2 { font-size: 15px; margin-top: 16px; margin-bottom: 8px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 14px; }
        th, td { border: 1px solid #ddd; padding: 6px; text-align: left; }
        th { background: #f3f3f3; }
    </style>
</head>
<body>
    <h1>{{ ucfirst($section) }} Report</h1>
    <p>Generated at: {{ now()->format('d M Y H:i') }}</p>

    @if($section === 'revenue')
        <h2>Summary</h2>
        <table>
            <tr><th>Total Revenue</th><td>RM {{ number_format($data['total'], 2) }}</td></tr>
        </table>

        <h2>Revenue by Trainer</h2>
        <table>
            <thead>
                <tr><th>Trainer</th><th>Total Revenue</th></tr>
            </thead>
            <tbody>
                @foreach($data['by_trainer'] as $row)
                    <tr>
                        <td>{{ $row->name }}</td>
                        <td>RM {{ number_format($row->total_revenue, 2) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @elseif($section === 'enrollments')
        <h2>Summary</h2>
        <table>
            <tr><th>Total Enrollments</th><td>{{ $data['total'] }}</td></tr>
        </table>

        <h2>By Status</h2>
        <table>
            <thead>
                <tr><th>Status</th><th>Total</th></tr>
            </thead>
            <tbody>
                @foreach($data['by_status'] as $row)
                    <tr>
                        <td>{{ ucfirst($row->status) }}</td>
                        <td>{{ $row->total }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @else
        <h2>Summary</h2>
        <table>
            <tr><th>Total Users</th><td>{{ $data['total'] }}</td></tr>
            <tr><th>Active Users</th><td>{{ $data['active'] }}</td></tr>
            <tr><th>Inactive Users</th><td>{{ $data['inactive'] }}</td></tr>
        </table>

        <h2>Monthly User Growth</h2>
        <table>
            <thead>
                <tr><th>Month</th><th>New Users</th></tr>
            </thead>
            <tbody>
                @foreach($data['growth'] as $row)
                    <tr>
                        <td>{{ $row['label'] }}</td>
                        <td>{{ $row['total'] }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif
</body>
</html>

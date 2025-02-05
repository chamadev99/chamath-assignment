<!DOCTYPE html>
<html>

<head>
    <title>Order Summary Report</title>
    <style>
        body {
            font-family: Arial, sans-serif;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th,
        td {
            border: 1px solid black;
            padding: 8px;
            text-align: left;
        }

        th {
            background-color: #f2f2f2;
        }
    </style>
</head>

<body>
    <h2 style="text-align: center;">Order Summary Report</h2>

    <table>
        <thead>
            <tr>
                <th></th>
                <th>Customer Name</th>
                <th>Email</th>
                <th>Order ID</th>
                <th>Order Date</th>
                <th>Product</th>
                <th>Price</th>
                <th>Quantity</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($orders as $index => $order)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td>{{ $order->order->customer->name ?? 'N/A' }}</td>
                    <td>{{ $order->order->customer->email ?? 'N/A' }}</td>
                    <td>{{ $order->order->order_reference }}</td>
                    <td>{{ \Carbon\Carbon::parse($order->order->order_date)->format('m/d/Y') }}</td>
                    <td>{{ $order->product->name }}</td>
                    <td>{{ $order->product->price }}</td>
                    <td>{{ $order->quantity }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>

</html>

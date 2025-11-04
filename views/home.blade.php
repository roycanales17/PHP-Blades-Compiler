<html lang="en">
<head>
	<title>Test page</title>
</head>
<body>
    @include('header')

    <h1>{{ 'Hello World!' }}</h1>
    <p>This is a testing content!</p>

    <ul>
        @foreach([ 'user 1', 'user 2', 'user 3' ] as $test)
            <li>{{ $test }}</li>
        @endforeach
    </ul>

    @include('footer')
</body>
</html>
<!-- resources/views/dev/index.blade.php -->
<x-app-layout>
    <div class="container mx-auto p-6">
        <h1 class="text-2xl font-semibold mb-6">SQL Executor (Admin Only)</h1>

        <!-- 错误消息 -->
        @if(isset($error))
            <div class="bg-red-500 text-white p-4 mb-4 rounded">
                {{ $error }}
            </div>
        @endif

        <!-- SQL 执行表单 -->
        <div class="mb-6">
            <form action="{{ route('dev') }}" method="POST">
                @csrf
                <textarea name="sql" class="w-full h-32 p-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="Enter your SQL query here...">{{ request('sql', '') }}</textarea>
                <div class="mt-4 flex justify-between">
                    <button type="submit" class="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600">Execute</button>
                </div>
            </form>
        </div>

        <!-- 如果有结果则展示 -->
        @if(isset($results))
            <h2 class="text-xl font-semibold mb-4">Query Results</h2>

            <!-- 如果查询结果为空 -->
            @if($results->isEmpty())
                <div class="bg-yellow-100 text-yellow-700 p-4 mb-4 rounded">
                    No results found.
                </div>
            @else
                <!-- 显示查询结果表格 -->
                <table class="min-w-full bg-white border border-gray-200 rounded-lg shadow-md">
                    <thead>
                    <tr class="bg-gray-100">
                        @foreach(array_keys((array) $results->first()) as $column)
                            <th class="py-2 px-4 border-b text-left text-gray-700">{{ ucfirst($column) }}</th>
                        @endforeach
                    </tr>
                    </thead>
                    <tbody>
                    @foreach($results as $row)
                        <tr>
                            @foreach((array) $row as $column)
                                <td class="py-2 px-4 border-b text-gray-600">{{ $column }}</td>
                            @endforeach
                        </tr>
                    @endforeach
                    </tbody>
                </table>

                <!-- 分页链接 -->
                <div class="mt-4">
                    {{ $results->appends(['sql' => request('sql', '')])->links() }}
                </div>
            @endif
        @endif
    </div>
</x-app-layout>

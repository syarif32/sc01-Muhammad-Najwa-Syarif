<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistem Reservasi Ruang Meeting</title>
    <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
</head>
<body class="bg-gray-100 text-gray-800 font-sans antialiased">
    <div class="max-w-6xl mx-auto p-6 space-y-6">
        
        <!-- HEADER -->
        <header class="bg-white p-6 rounded-lg shadow-sm border border-gray-200 flex justify-between items-center">
            <div>
                <h1 class="text-2xl font-bold text-indigo-600">Sistem Reservasi Ruang Meeting</h1>
                <p class="text-sm text-gray-500">Dashboard lengkap memenuhi kriteria Mandatory (CRUD Room, Booking, Overlap Check, & Cancel Policy)</p>
            </div>
            <span class="text-xs bg-indigo-50 text-indigo-700 font-semibold px-3 py-1 rounded-full border border-indigo-200">Full Feature UI</span>
        </header>
        @if(session('success'))
            <div class="p-4 bg-green-50 border border-green-200 text-green-700 rounded-lg text-sm font-medium">
                {{ session('success') }}
            </div>
        @endif

        @if(session('error'))
            <div class="p-4 bg-red-50 border border-red-200 text-red-700 rounded-lg text-sm font-medium">
                {{ session('error') }}
            </div>
        @endif

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            
            <!-- MANAJEMEN RUANGAN -->
            <div class="space-y-6">
                <div class="bg-white p-6 rounded-lg shadow-sm border border-gray-200">
                    <h2 class="text-md font-semibold mb-4 text-gray-700">1. Tambah Ruangan Baru</h2>
                    <form action="{{ route('room.store') }}" method="POST" class="space-y-3">
                        @csrf
                        <div>
                            <label class="block text-xs font-medium text-gray-600 uppercase mb-1">Nama Ruangan</label>
                            <input type="text" name="name" required class="w-full border-gray-300 rounded-md border p-2 text-sm" placeholder="Contoh: Ruang Alpha">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-600 uppercase mb-1">Kapasitas (Orang)</label>
                            <input type="number" name="capacity" min="1" required class="w-full border-gray-300 rounded-md border p-2 text-sm" placeholder="Contoh: 10">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-600 uppercase mb-1">Lokasi</label>
                            <input type="text" name="location" required class="w-full border-gray-300 rounded-md border p-2 text-sm" placeholder="Contoh: Lantai 2">
                        </div>
                        <button type="submit" class="w-full bg-indigo-600 text-white font-medium py-2 px-4 rounded-md hover:bg-indigo-700 transition text-sm">
                            Simpan Ruangan
                        </button>
                    </form>
                </div>

                <!-- DAFTAR RUANGAN & HAPUS -->
                <div class="bg-white p-6 rounded-lg shadow-sm border border-gray-200">
                    <h3 class="text-md font-semibold mb-3 text-gray-700">Daftar Ruangan Tersedia</h3>
                    <div class="space-y-2 max-h-60 overflow-y-auto">
                        @forelse($rooms as $r)
                            <div class="flex justify-between items-center p-2.5 bg-gray-50 border border-gray-200 rounded text-sm">
                                <div>
                                    <p class="font-medium text-gray-800">{{ $r->name }}</p>
                                    <p class="text-xs text-gray-500">Kapasitas: {{ $r->capacity }} | {{ $r->location }}</p>
                                </div>
                                <form action="{{ route('room.destroy', $r->id) }}" method="POST" onsubmit="return confirm('Yakin ingin menghapus ruangan ini?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-red-500 hover:text-red-700 text-xs font-semibold px-2 py-1 bg-red-50 rounded border border-red-200">Hapus</button>
                                </form>
                            </div>
                        @empty
                            <p class="text-xs text-gray-400 italic">Belum ada ruangan terdaftar.</p>
                        @endforelse
                    </div>
                </div>
            </div>
            <div class="lg:col-span-2 space-y-6">
                
                <!-- FILTER RUANGAN & TANGGAL -->
                <div class="bg-white p-6 rounded-lg shadow-sm border border-gray-200">
                    <h2 class="text-md font-semibold mb-4 text-gray-700">2. Lihat & Filter Jadwal Booking</h2>
                    <form method="GET" action="{{ route('meeting.index') }}" class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                            <label class="block text-xs font-medium text-gray-600 uppercase mb-1">Pilih Ruangan</label>
                            <select name="room_id" required class="w-full border-gray-300 rounded-md border p-2 text-sm">
                                <option value="">-- Pilih --</option>
                                @foreach($rooms as $room)
                                    <option value="{{ $room->id }}" {{ (isset($selectedRoom) && $selectedRoom->id == $room->id) ? 'selected' : '' }}>
                                        {{ $room->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-600 uppercase mb-1">Tanggal</label>
                            <input type="date" name="date" value="{{ $date }}" required class="w-full border-gray-300 rounded-md border p-2 text-sm">
                        </div>
                        <div class="flex items-end">
                            <button type="submit" class="w-full bg-gray-800 text-white font-medium py-2 px-4 rounded-md hover:bg-gray-900 transition text-sm">
                                Filter Jadwal
                            </button>
                        </div>
                    </form>
                </div>

                @if($selectedRoom)
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        
                        <!-- DAFTAR BOOKING & TOMBOL PEMBATALAN -->
                        <div class="bg-white p-6 rounded-lg shadow-sm border border-gray-200">
                            <h3 class="text-md font-semibold mb-1 text-gray-700">Jadwal: {{ $selectedRoom->name }}</h3>
                            <p class="text-xs text-gray-400 mb-4">Tanggal: {{ $date }}</p>

                            <div class="space-y-3 max-h-80 overflow-y-auto">
                                @forelse($bookings as $b)
                                    <div class="p-3 rounded border text-sm {{ $b->status === 'cancelled' ? 'bg-gray-50 border-gray-200 text-gray-400' : 'bg-indigo-50 border-indigo-100 text-indigo-900' }}">
                                        <div class="flex justify-between font-semibold items-center">
                                            <span>{{ \Carbon\Carbon::parse($b->start_time)->format('H:i') }} - {{ \Carbon\Carbon::parse($b->end_time)->format('H:i') }}</span>
                                            <span class="uppercase text-[10px] px-2 py-0.5 rounded {{ $b->status === 'cancelled' ? 'bg-gray-200 text-gray-600' : 'bg-green-100 text-green-800' }}">
                                                {{ $b->status }}
                                            </span>
                                        </div>
                                        <p class="text-xs mt-1 text-gray-600">Owner User ID: <strong>#{{ $b->user_id }}</strong></p>
                                        
                                       
                                        @if($b->status === 'confirmed')
                                            <form action="{{ route('booking.cancel', $b->id) }}" method="POST" class="mt-2 pt-2 border-t border-indigo-100 flex items-center space-x-2">
                                                @csrf
                                                @method('PATCH')
                                                <input type="number" name="user_id" placeholder="ID Anda" required class="w-20 border border-gray-300 rounded px-1.5 py-1 text-xs bg-white">
                                                <button type="submit" class="text-xs bg-red-600 text-white px-2.5 py-1 rounded hover:bg-red-700 transition">
                                                    Batalkan
                                                </button>
                                            </form>
                                        @endif
                                    </div>
                                @empty
                                    <p class="text-sm text-gray-400 italic">Belum ada jadwal reservasi pada tanggal ini.</p>
                                @endforelse
                            </div>
                        </div>

                        <!-- FORM RESERVASI -->
                        <div class="bg-white p-6 rounded-lg shadow-sm border border-gray-200">
                            <h3 class="text-md font-semibold mb-3 text-gray-700">3. Buat Reservasi Baru</h3>
                            
                            <form action="{{ route('booking.store') }}" method="POST" class="space-y-3">
                                @csrf
                                <input type="hidden" name="room_id" value="{{ $selectedRoom->id }}">
                                
                                <div>
                                    <label class="block text-xs font-medium text-gray-600 uppercase mb-1">User ID</label>
                                    <input type="number" name="user_id" value="1" required class="w-full border-gray-300 rounded-md border p-2 text-sm" placeholder="Contoh: 1">
                                </div>

                                <div>
                                    <label class="block text-xs font-medium text-gray-600 uppercase mb-1">Waktu Mulai</label>
                                    <input type="datetime-local" name="start_time" required class="w-full border-gray-300 rounded-md border p-2 text-sm">
                                </div>

                                <div>
                                    <label class="block text-xs font-medium text-gray-600 uppercase mb-1">Waktu Selesai</label>
                                    <input type="datetime-local" name="end_time" required class="w-full border-gray-300 rounded-md border p-2 text-sm">
                                </div>

                                <button type="submit" class="w-full bg-green-600 text-white font-medium py-2 px-4 rounded-md hover:bg-green-700 transition text-sm">
                                    Buat Reservasi
                                </button>
                            </form>
                        </div>
                    </div>
                @endif

            </div>

        </div>
    </div>
</body>
</html>
{{-- ID Card Preview Component --}}
<div class="id-card-preview-wrap">
    <div class="id-scale-box">

        <div class="student-id-card">

            <div class="row h-100">

                <!-- LEFT -->
                <div class="col-8 d-flex flex-column">
                    <div class="id-card-profile-box mt-1 ms-1">
                        <img src="{{ $studentImage }}" alt="Student Photo"
                            onerror="this.onerror=null;this.src='{{ asset('storage/logo/black_logo.png') }}'">
                    </div>

                    <div class="ms-1 mt-3">
                        <div class="id-card-student-id">
                            {{ $studentData['custom_id'] ?? 'N/A' }}
                        </div>

                        <div class="id-card-student-name mt-1">
                            {{ $studentData['name'] ?? 'Student Name' }}
                        </div>

                        <div class="id-card-address mt-1">
                            {{ $studentData['address'] ?? 'Address not available' }}
                        </div>
                    </div>
                </div>

                <!-- RIGHT -->
                <div class="col-4 d-flex flex-column align-items-center">

                    @php
                        $qrData = $studentData['custom_id'] ?? 'N/A';
                        $qrUrl = 'https://api.qrserver.com/v1/create-qr-code/?size=600x600&data=' . urlencode($qrData);
                    @endphp

                    <img src="{{ $qrUrl }}" class="id-card-qr-img mt-1" alt="QR Code">

                    <img src="{{ asset('storage/logo/black_logo.png') }}" class="id-card-logo mt-auto mb-1" alt="Logo">

                </div>

            </div>

        </div>

    </div>
</div>

<style>
    @font-face {
        font-family: 'Monbaiti';
        src: url('{{ asset('fonts/monbaiti.ttf') }}') format('truetype');
        font-weight: normal;
        font-style: normal;
    }

    .student-id-card {
        width: 86mm;
        height: 54mm;
        background: url('{{ asset('storage/id/idcard_bg.png') }}') no-repeat center;
        background-size: cover;
        border-radius: 3mm;
        padding: 3mm;
        box-shadow: 0 2mm 5mm rgba(0, 0, 0, .25);
        margin: 0 auto;
        position: relative;
        font-family: 'Monbaiti', serif !important;
    }

    .id-card-profile-box {
        width: 18mm;
        height: 22mm;
        border: 0.3mm solid #ccc;
        border-radius: 1mm;
        overflow: hidden;
        background: #fff;
    }

    .id-card-profile-box img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }

    .id-card-student-id {
        font-size: 4.5mm;
        font-weight: bold;
        line-height: 1.1;
        color: #000;
    }

    .id-card-student-name {
        font-size: 4.3mm;
        line-height: 1.2;
        color: #000;
        margin-top: 0.5mm;
    }

    .id-card-address {
        font-size: 3mm;
        line-height: 1.2;
        color: #000;
        margin-top: 0.5mm;
        max-width: 45mm;
        overflow: hidden;
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        word-wrap: break-word;
    }

    .id-card-qr-img {
        width: 18mm;
        height: 18mm;
        background: #fff;
        padding: 1mm;
        border-radius: 1mm;
    }

    .id-card-logo {
        width: 30mm;
    }
</style>
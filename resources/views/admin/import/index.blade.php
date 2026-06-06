@extends('layouts.app')

@section('title', 'Import Data')
@section('page-title', 'Import Data')

@section('content')

    <div class="import-data-page">

        {{-- Success Message --}}
        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show rounded-3 shadow-sm">
                <i class="bi bi-check-circle-fill me-2"></i>
                {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        {{-- Error Message --}}
        @if(session('error'))
            <div class="alert alert-danger alert-dismissible fade show rounded-3 shadow-sm">
                <i class="bi bi-exclamation-triangle-fill me-2"></i>
                {{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        {{-- Validation Errors --}}
        @if ($errors->any())
            <div class="alert alert-danger alert-dismissible fade show rounded-3 shadow-sm">
                <i class="bi bi-exclamation-circle-fill me-2"></i>
                <strong>Please fix the following errors:</strong>
                <ul class="mb-0 mt-2">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        {{-- Import Cards Grid --}}
        <div class="row g-4">

            {{-- Import Students --}}
            <div class="col-xl-4 col-lg-6 col-md-6">
                <div class="import-card">
                    <div class="import-card-icon">
                        <i class="bi bi-people-fill"></i>
                    </div>
                    <h3 class="import-card-title">Import Students</h3>
                    <p class="import-card-desc">Bulk import student data from CSV file</p>

                    <form action="{{ route('admin.students.import') }}" method="POST" enctype="multipart/form-data"
                        class="import-form">
                        @csrf
                        <div class="file-upload-wrapper">
                            <input type="file" name="file" id="student_file" class="file-input" accept=".csv,.txt" required>
                            <label for="student_file" class="file-label">
                                <i class="bi bi-cloud-upload"></i>
                                <span>Choose CSV File</span>
                            </label>
                            <span class="file-name" id="student_file_name"></span>
                        </div>
                        <button type="submit" class="btn-import">
                            <i class="bi bi-upload"></i> Import Students
                        </button>
                    </form>
                </div>
            </div>

            {{-- Import Quick Photos --}}
            <div class="col-xl-4 col-lg-6 col-md-6">
                <div class="import-card">
                    <div class="import-card-icon" style="background: linear-gradient(135deg, #8b5cf6, #7c3aed);">
                        <i class="bi bi-images"></i>
                    </div>
                    <h3 class="import-card-title">Import Quick Photos</h3>
                    <p class="import-card-desc">Bulk import student photos from ZIP file</p>

                    <form action="{{ route('admin.quickphotos.import') }}" method="POST" enctype="multipart/form-data"
                        class="import-form">
                        @csrf
                        <div class="file-upload-wrapper">
                            <input type="file" name="file" id="photos_file" class="file-input" accept=".zip,.csv,.txt"
                                required>
                            <label for="photos_file" class="file-label">
                                <i class="bi bi-cloud-upload"></i>
                                <span>Choose ZIP/CSV File</span>
                            </label>
                            <span class="file-name" id="photos_file_name"></span>
                        </div>
                        <button type="submit" class="btn-import">
                            <i class="bi bi-upload"></i> Import Quick Photos
                        </button>
                    </form>
                </div>
            </div>

            {{-- Import Teachers --}}
            <div class="col-xl-4 col-lg-6 col-md-6">
                <div class="import-card">
                    <div class="import-card-icon" style="background: linear-gradient(135deg, #f59e0b, #d97706);">
                        <i class="bi bi-person-badge"></i>
                    </div>
                    <h3 class="import-card-title">Import Teachers</h3>
                    <p class="import-card-desc">Bulk import teacher data from CSV file</p>

                    <form action="{{ route('admin.teachers.import') }}" method="POST" enctype="multipart/form-data"
                        class="import-form">
                        @csrf
                        <div class="file-upload-wrapper">
                            <input type="file" name="file" id="teacher_file" class="file-input" accept=".csv,.txt" required>
                            <label for="teacher_file" class="file-label">
                                <i class="bi bi-cloud-upload"></i>
                                <span>Choose CSV File</span>
                            </label>
                            <span class="file-name" id="teacher_file_name"></span>
                        </div>
                        <button type="submit" class="btn-import">
                            <i class="bi bi-upload"></i> Import Teachers
                        </button>
                    </form>
                </div>
            </div>

            {{-- Import Classes --}}
            <div class="col-xl-4 col-lg-6 col-md-6">
                <div class="import-card">
                    <div class="import-card-icon" style="background: linear-gradient(135deg, #ef4444, #dc2626);">
                        <i class="bi bi-book"></i>
                    </div>
                    <h3 class="import-card-title">Import Classes</h3>
                    <p class="import-card-desc">Bulk import class data from CSV file</p>

                    <form action="{{ route('admin.classes.import') }}" method="POST" enctype="multipart/form-data"
                        class="import-form">
                        @csrf
                        <div class="file-upload-wrapper">
                            <input type="file" name="file" id="class_file" class="file-input" accept=".csv,.txt" required>
                            <label for="class_file" class="file-label">
                                <i class="bi bi-cloud-upload"></i>
                                <span>Choose CSV File</span>
                            </label>
                            <span class="file-name" id="class_file_name"></span>
                        </div>
                        <button type="submit" class="btn-import">
                            <i class="bi bi-upload"></i> Import Classes
                        </button>
                    </form>
                </div>
            </div>

            {{-- Import Class Category Fees --}}
            <div class="col-xl-4 col-lg-6 col-md-6">
                <div class="import-card">
                    <div class="import-card-icon" style="background: linear-gradient(135deg, #10b981, #059669);">
                        <i class="bi bi-tags"></i>
                    </div>
                    <h3 class="import-card-title">Import Class Category Fees</h3>
                    <p class="import-card-desc">Bulk import fee structures from CSV file</p>

                    <form action="{{ route('admin.class-category-fees.import') }}" method="POST"
                        enctype="multipart/form-data" class="import-form">
                        @csrf
                        <div class="file-upload-wrapper">
                            <input type="file" name="file" id="fee_file" class="file-input" accept=".csv,.txt" required>
                            <label for="fee_file" class="file-label">
                                <i class="bi bi-cloud-upload"></i>
                                <span>Choose CSV File</span>
                            </label>
                            <span class="file-name" id="fee_file_name"></span>
                        </div>
                        <button type="submit" class="btn-import">
                            <i class="bi bi-upload"></i> Import Fees
                        </button>
                    </form>
                </div>
            </div>

            {{-- Import Class Enrollments --}}
            <div class="col-xl-4 col-lg-6 col-md-6">
                <div class="import-card">
                    <div class="import-card-icon" style="background: linear-gradient(135deg, #06b6d4, #0891b2);">
                        <i class="bi bi-person-plus"></i>
                    </div>
                    <h3 class="import-card-title">Import Class Enrollments</h3>
                    <p class="import-card-desc">Bulk import student enrollments from CSV file</p>

                    <form action="{{ route('admin.student-class-enrollments.import') }}" method="POST"
                        enctype="multipart/form-data" class="import-form">
                        @csrf
                        <div class="file-upload-wrapper">
                            <input type="file" name="file" id="enrollment_file" class="file-input" accept=".csv,.txt"
                                required>
                            <label for="enrollment_file" class="file-label">
                                <i class="bi bi-cloud-upload"></i>
                                <span>Choose CSV File</span>
                            </label>
                            <span class="file-name" id="enrollment_file_name"></span>
                        </div>
                        <button type="submit" class="btn-import">
                            <i class="bi bi-upload"></i> Import Enrollments
                        </button>
                    </form>
                </div>
            </div>

            {{-- Import Class Schedules --}}
            <div class="col-xl-4 col-lg-6 col-md-6">
                <div class="import-card">
                    <div class="import-card-icon" style="background: linear-gradient(135deg, #ec489a, #db2777);">
                        <i class="bi bi-calendar"></i>
                    </div>
                    <h3 class="import-card-title">Import Class Schedules</h3>
                    <p class="import-card-desc">Bulk import class schedules from CSV file</p>

                    <form action="{{ route('admin.class-schedules.import') }}" method="POST" enctype="multipart/form-data"
                        class="import-form">
                        @csrf
                        <div class="file-upload-wrapper">
                            <input type="file" name="file" id="schedule_file" class="file-input" accept=".csv,.txt"
                                required>
                            <label for="schedule_file" class="file-label">
                                <i class="bi bi-cloud-upload"></i>
                                <span>Choose CSV File</span>
                            </label>
                            <span class="file-name" id="schedule_file_name"></span>
                        </div>
                        <button type="submit" class="btn-import">
                            <i class="bi bi-upload"></i> Import Schedules
                        </button>
                    </form>
                </div>
            </div>

            {{-- Import Attendances --}}
            <div class="col-xl-4 col-lg-6 col-md-6">
                <div class="import-card">
                    <div class="import-card-icon" style="background: linear-gradient(135deg, #14b8a6, #0d9488);">
                        <i class="bi bi-check-circle"></i>
                    </div>
                    <h3 class="import-card-title">Import Attendances</h3>
                    <p class="import-card-desc">Bulk import attendance records from CSV file</p>

                    <form action="{{ route('admin.student-attendances.import') }}" method="POST"
                        enctype="multipart/form-data" class="import-form">
                        @csrf
                        <div class="file-upload-wrapper">
                            <input type="file" name="file" id="attendance_file" class="file-input" accept=".csv,.txt"
                                required>
                            <label for="attendance_file" class="file-label">
                                <i class="bi bi-cloud-upload"></i>
                                <span>Choose CSV File</span>
                            </label>
                            <span class="file-name" id="attendance_file_name"></span>
                        </div>
                        <button type="submit" class="btn-import">
                            <i class="bi bi-upload"></i> Import Attendances
                        </button>
                    </form>
                </div>
            </div>

            {{-- Import Payments --}}
            <div class="col-xl-4 col-lg-6 col-md-6">
                <div class="import-card">
                    <div class="import-card-icon" style="background: linear-gradient(135deg, #f97316, #ea580c);">
                        <i class="bi bi-currency-dollar"></i>
                    </div>
                    <h3 class="import-card-title">Import Payments</h3>
                    <p class="import-card-desc">Bulk import payment records from CSV file</p>

                    <form action="{{ route('admin.payments.import') }}" method="POST" enctype="multipart/form-data"
                        class="import-form">
                        @csrf
                        <div class="file-upload-wrapper">
                            <input type="file" name="file" id="payment_file" class="file-input" accept=".csv,.txt" required>
                            <label for="payment_file" class="file-label">
                                <i class="bi bi-cloud-upload"></i>
                                <span>Choose CSV File</span>
                            </label>
                            <span class="file-name" id="payment_file_name"></span>
                        </div>
                        <button type="submit" class="btn-import">
                            <i class="bi bi-upload"></i> Import Payments
                        </button>
                    </form>
                </div>
            </div>

        </div>

        {{-- Help Section --}}
        <div class="help-section">
            <div class="help-header">
                <i class="bi bi-question-circle-fill"></i>
                <h4>Need Help with Import?</h4>
            </div>
            <p>Follow these guidelines for successful data import:</p>
            <div class="help-grid">
                <div class="help-item">
                    <i class="bi bi-filetype-csv"></i>
                    <span>Use CSV format files only</span>
                </div>
                <div class="help-item">
                    <i class="bi bi-columns-gap"></i>
                    <span>Match column headers with template</span>
                </div>
                <div class="help-item">
                    <i class="bi bi-download"></i>
                    <a href="#" class="download-template">Download Sample Template</a>
                </div>
            </div>
        </div>

    </div>

@endsection

@push('styles')
    <style>
        .import-data-page {
            animation: fadeInUp 0.4s ease;
        }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* Import Cards */
        .import-card {
            background: #ffffff;
            border-radius: 24px;
            padding: 1.5rem;
            height: 100%;
            transition: all 0.3s ease;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.04);
            border: 1px solid #eef2f7;
            position: relative;
            overflow: hidden;
        }

        .import-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 3px;
            background: linear-gradient(90deg, #10b981, #3b82f6);
        }

        .import-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 20px 35px rgba(0, 0, 0, 0.08);
        }

        .import-card-icon {
            width: 55px;
            height: 55px;
            background: linear-gradient(135deg, #4f46e5, #7c3aed);
            border-radius: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 1rem;
            box-shadow: 0 8px 16px rgba(79, 70, 229, 0.2);
        }

        .import-card-icon i {
            font-size: 1.5rem;
            color: white;
        }

        .import-card-title {
            font-size: 1.1rem;
            font-weight: 700;
            color: #1e293b;
            margin-bottom: 0.25rem;
        }

        .import-card-desc {
            font-size: 0.8rem;
            color: #64748b;
            margin-bottom: 1rem;
        }

        /* File Upload */
        .import-form {
            margin-top: 0.5rem;
        }

        .file-upload-wrapper {
            position: relative;
            margin-bottom: 1rem;
        }

        .file-input {
            position: absolute;
            opacity: 0;
            width: 0;
            height: 0;
        }

        .file-label {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            padding: 0.6rem 1rem;
            background: #f8fafc;
            border: 1px dashed #cbd5e1;
            border-radius: 12px;
            cursor: pointer;
            transition: all 0.3s ease;
            font-size: 0.85rem;
            font-weight: 500;
            color: #475569;
        }

        .file-label:hover {
            border-color: #10b981;
            background: #f0fdf4;
            color: #10b981;
        }

        .file-label i {
            font-size: 1rem;
        }

        .file-name {
            display: block;
            font-size: 0.7rem;
            color: #64748b;
            margin-top: 0.3rem;
            text-align: center;
            word-break: break-all;
        }

        /* Import Button */
        .btn-import {
            width: 100%;
            background: linear-gradient(135deg, #4f46e5, #7c3aed);
            color: white;
            border: none;
            padding: 0.6rem 1rem;
            border-radius: 12px;
            font-weight: 600;
            font-size: 0.85rem;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
        }

        .btn-import:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(79, 70, 229, 0.3);
            color: white;
        }

        /* Help Section */
        .help-section {
            background: #f8fafc;
            border-radius: 24px;
            padding: 1.5rem;
            margin-top: 2rem;
            text-align: center;
            border: 1px solid #eef2f7;
        }

        .help-header {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            margin-bottom: 0.5rem;
        }

        .help-header i {
            font-size: 1.5rem;
            color: #10b981;
        }

        .help-header h4 {
            font-size: 1.1rem;
            font-weight: 700;
            color: #1e293b;
            margin: 0;
        }

        .help-section>p {
            font-size: 0.8rem;
            color: #64748b;
            margin-bottom: 1rem;
        }

        .help-grid {
            display: flex;
            justify-content: center;
            gap: 1.5rem;
            flex-wrap: wrap;
        }

        .help-item {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.8rem;
            color: #475569;
            background: white;
            padding: 0.4rem 1rem;
            border-radius: 30px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
        }

        .help-item i {
            color: #10b981;
            font-size: 0.9rem;
        }

        .download-template {
            color: #4f46e5;
            text-decoration: none;
            font-weight: 500;
        }

        .download-template:hover {
            text-decoration: underline;
        }

        /* Alerts */
        .alert {
            border: none;
            border-radius: 16px;
            padding: 1rem 1.25rem;
            margin-bottom: 1.5rem;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .import-card {
                padding: 1.2rem;
            }

            .help-grid {
                flex-direction: column;
                align-items: center;
                gap: 0.75rem;
            }

            .help-item {
                width: 100%;
                justify-content: center;
            }
        }
    </style>
@endpush

@push('scripts')
    <script>
        // File name display for all file inputs
        const fileInputs = [
            { id: 'student_file', nameId: 'student_file_name' },
            { id: 'photos_file', nameId: 'photos_file_name' },
            { id: 'teacher_file', nameId: 'teacher_file_name' },
            { id: 'class_file', nameId: 'class_file_name' },
            { id: 'fee_file', nameId: 'fee_file_name' },
            { id: 'enrollment_file', nameId: 'enrollment_file_name' },
            { id: 'schedule_file', nameId: 'schedule_file_name' },
            { id: 'attendance_file', nameId: 'attendance_file_name' },
            { id: 'payment_file', nameId: 'payment_file_name' }
        ];

        fileInputs.forEach(input => {
            const fileElement = document.getElementById(input.id);
            const nameElement = document.getElementById(input.nameId);

            if (fileElement && nameElement) {
                fileElement.addEventListener('change', function () {
                    if (this.files && this.files[0]) {
                        nameElement.textContent = this.files[0].name;
                    } else {
                        nameElement.textContent = '';
                    }
                });
            }
        });
    </script>
@endpush
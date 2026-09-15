<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dynamic Risk Assessment Form - Vesperia Test</title>
    <style>
        * {
            box-sizing: border-box;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
        }
        body {
            background-color: #f3f4f6;
            color: #1f2937;
            padding: 24px;
            margin: 0;
        }
        .container {
            max-width: 800px;
            margin: 0 auto;
            background: #ffffff;
            padding: 32px;
            border-radius: 8px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        }
        .header-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 2px solid #e5e7eb;
            padding-bottom: 12px;
            margin-bottom: 20px;
        }
        h1 {
            margin: 0;
            font-size: 24px;
            color: #111827;
        }
        .btn-secondary {
            background-color: #f3f4f6;
            color: #374151;
            border: 1px solid #d1d5db;
            padding: 6px 12px;
            border-radius: 4px;
            font-size: 13px;
            cursor: pointer;
        }
        .btn-secondary:hover {
            background-color: #e5e7eb;
        }
        .upload-card {
            border: 2px dashed #93c5fd;
            background: #eff6ff;
            border-radius: 8px;
            padding: 32px;
            text-align: center;
            margin-bottom: 24px;
        }
        .upload-title {
            font-size: 18px;
            font-weight: 600;
            color: #1e40af;
            margin-bottom: 8px;
        }
        .upload-desc {
            font-size: 14px;
            color: #4b5563;
            margin-bottom: 20px;
        }
        .section-card {
            margin-bottom: 32px;
            border: 1px solid #e5e7eb;
            border-radius: 6px;
            padding: 20px;
            background: #fafafa;
        }
        .section-title {
            font-size: 18px;
            font-weight: 600;
            color: #1e40af;
            margin-top: 0;
            margin-bottom: 16px;
        }
        .form-group {
            margin-bottom: 18px;
            background: #ffffff;
            padding: 14px;
            border-radius: 6px;
            border: 1px solid #f0f0f0;
        }
        .form-label {
            display: block;
            font-weight: 600;
            font-size: 14px;
            margin-bottom: 4px;
            color: #374151;
        }
        .form-desc {
            font-size: 12px;
            color: #6b7280;
            margin-bottom: 8px;
        }
        .form-control {
            width: 100%;
            padding: 8px 12px;
            border: 1px solid #d1d5db;
            border-radius: 4px;
            font-size: 14px;
        }
        .form-control:focus {
            outline: none;
            border-color: #2563eb;
            box-shadow: 0 0 0 2px rgba(37, 99, 235, 0.2);
        }
        .options-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
            gap: 8px;
            margin-top: 6px;
        }
        .option-item {
            display: flex;
            align-items: center;
            gap: 6px;
            font-size: 13px;
        }
        .btn-submit {
            background-color: #2563eb;
            color: #ffffff;
            padding: 12px 24px;
            font-size: 15px;
            font-weight: 600;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            width: 100%;
            transition: background-color 0.2s;
        }
        .btn-submit:hover {
            background-color: #1d4ed8;
        }
        .btn-submit:disabled {
            background-color: #9ca3af;
            cursor: not-allowed;
        }
        .alert {
            padding: 14px;
            border-radius: 6px;
            margin-bottom: 20px;
            display: none;
            transition: opacity 0.3s ease;
        }
        .alert-success {
            background-color: #d1fae5;
            color: #065f46;
            border: 1px solid #a7f3d0;
        }
        .alert-danger {
            background-color: #fee2e2;
            color: #991b1b;
            border: 1px solid #fecaca;
        }
        .loading {
            text-align: center;
            padding: 40px;
            color: #6b7280;
        }
    </style>
</head>
<body>

<div class="container">
    <div class="header-row">
        <h1>Dynamic Risk Assessment Form</h1>
        <button id="btnToggleUpload" class="btn-secondary" style="display: none;" onclick="toggleUploadBox()">
            Unggah File JSON Baru
        </button>
    </div>
    
    <div id="alertBox" class="alert"></div>
    <div id="loadingBox" class="loading">Memuat skema form dari API...</div>

    <!-- 1. Panel Upload JSON (Muncul otomatis jika database kosong) -->
    <div id="uploadBox" class="upload-card" style="display: none;">
        <div class="upload-title">Skema Form Belum Ada di Database</div>
        <div class="upload-desc">Silakan pilih file <code>submission.json</code> dari komputer Anda untuk langsung mengisi skema database secara otomatis.</div>
        
        <form id="uploadFeedForm" style="max-width: 450px; margin: 0 auto;">
            <input type="file" id="jsonFileInput" class="form-control" accept=".json,application/json" required style="margin-bottom: 12px; background: white;" />
            <button type="submit" id="btnUploadFeed" class="btn-submit">Unggah & Terapkan Skema JSON</button>
        </form>
    </div>

    <!-- 2. Form Dinamis (Muncul jika database sudah terisi) -->
    <form id="dynamicForm" style="display: none;">
        <div class="form-group" style="background: #eef2ff; border-color: #c7d2fe;">
            <label class="form-label" for="submissionTitle">Judul / Keterangan Submission</label>
            <input type="text" id="submissionTitle" class="form-control" placeholder="Contoh: Laporan Risiko Operasional Q1">
        </div>

        <div id="sectionsContainer"></div>

        <button type="submit" id="btnSubmit" class="btn-submit">Kirim Form (Submit to API)</button>
    </form>
</div>

<script>
    const API_BASE = '/api';
    let formSchema = [];
    let alertTimer = null;

    // 1. Fetch Form Schema saat halaman dimuat
    document.addEventListener('DOMContentLoaded', () => {
        loadFormSchema();
    });

    async function loadFormSchema() {
        showLoading(true);
        try {
            const response = await fetch(`${API_BASE}/form-schema`);
            const json = await response.json();

            if (json.success || json.status === 'success') {
                formSchema = json.data || [];

                // KONDISIONAL: Jika database kosong
                if (formSchema.length === 0) {
                    document.getElementById('uploadBox').style.display = 'block';
                    document.getElementById('dynamicForm').style.display = 'none';
                    document.getElementById('btnToggleUpload').style.display = 'none';
                } 
                // KONDISIONAL: Jika database sudah ada isinya
                else {
                    document.getElementById('uploadBox').style.display = 'none';
                    document.getElementById('dynamicForm').style.display = 'block';
                    document.getElementById('btnToggleUpload').style.display = 'block';
                    renderForm(formSchema);
                }
            } else {
                throw new Error(json.message || 'Gagal memuat skema form');
            }
        } catch (err) {
            showAlert(`Gagal terhubung ke API: ${err.message}`, 'danger');
            document.getElementById('uploadBox').style.display = 'block';
        } finally {
            showLoading(false);
        }
    }

    // 2. Handle Upload File JSON dari Frontend
    document.getElementById('uploadFeedForm').addEventListener('submit', async (e) => {
        e.preventDefault();
        const fileInput = document.getElementById('jsonFileInput');
        if (!fileInput.files || fileInput.files.length === 0) {
            showAlert('Silakan pilih file .json terlebih dahulu!', 'danger');
            return;
        }

        const btn = document.getElementById('btnUploadFeed');
        btn.disabled = true;
        btn.innerText = 'Mengunggah & memproses feed...';

        const formData = new FormData();
        formData.append('feed_file', fileInput.files[0]);

        try {
            const response = await fetch(`${API_BASE}/upload-feed`, {
                method: 'POST',
                headers: {
                    'Accept': 'application/json'
                },
                body: formData
            });

            const result = await response.json();

            if (response.ok && result.success) {
                showAlert('Sukses! File JSON berhasil diproses dan disimpan ke database.', 'success', 4000);
                fileInput.value = '';
                await loadFormSchema();
            } else {
                throw new Error(result.message || 'Gagal mengunggah file JSON');
            }
        } catch (err) {
            showAlert(`Error: ${err.message}`, 'danger');
        } finally {
            btn.disabled = false;
            btn.innerText = 'Unggah & Terapkan Skema JSON';
        }
    });

    // 3. Render Form Dinamis
    function renderForm(sections) {
        const container = document.getElementById('sectionsContainer');
        container.innerHTML = '';

        sections.forEach(section => {
            const secCard = document.createElement('div');
            secCard.className = 'section-card';
            secCard.innerHTML = `<h2 class="section-title">${section.name}</h2>`;

            section.payloads.forEach(field => {
                const group = document.createElement('div');
                group.className = 'form-group';
                group.setAttribute('data-field-id', field.id);
                group.setAttribute('data-field-type', field.type);

                let fieldHtml = `
                    <label class="form-label">${field.label}</label>
                    ${field.description ? `<div class="form-desc">${field.description}</div>` : ''}
                `;

                const initialValue = field.answer ? field.answer.value : '';

                if (field.type === 'radio_button') {
                    let selectedId = '';
                    if (Array.isArray(initialValue) && initialValue.length > 0) {
                        selectedId = initialValue[0].id || initialValue[0];
                    }

                    fieldHtml += `<div class="options-grid">`;
                    field.options.forEach(opt => {
                        const checked = opt.id === selectedId ? 'checked' : '';
                        fieldHtml += `
                            <label class="option-item">
                                <input type="radio" name="field_${field.id}" value="${opt.id}" ${checked}>
                                ${opt.label}
                            </label>
                        `;
                    });
                    fieldHtml += `</div>`;
                } else if (field.type === 'checkbox') {
                    const selectedIds = Array.isArray(initialValue) ? initialValue : [];

                    fieldHtml += `<div class="options-grid">`;
                    field.options.forEach(opt => {
                        const checked = selectedIds.includes(opt.id) ? 'checked' : '';
                        fieldHtml += `
                            <label class="option-item">
                                <input type="checkbox" name="field_${field.id}" value="${opt.id}" ${checked}>
                                ${opt.label}
                            </label>
                        `;
                    });
                    fieldHtml += `</div>`;
                } else if (field.type === 'long_text') {
                    const val = typeof initialValue === 'string' ? initialValue : '';
                    fieldHtml += `<textarea name="field_${field.id}" class="form-control" rows="3">${val}</textarea>`;
                } else if (field.type === 'text') {
                    const val = (initialValue !== null && initialValue !== undefined) ? initialValue : '';
                    if (field.sub_type === 'date') {
                        fieldHtml += `<input type="date" name="field_${field.id}" class="form-control" value="${val !== '-' ? val : ''}">`;
                    } else if (field.sub_type === 'amount') {
                        fieldHtml += `<input type="number" step="any" name="field_${field.id}" class="form-control" value="${val !== '-' ? val : ''}">`;
                    } else {
                        fieldHtml += `<input type="text" name="field_${field.id}" class="form-control" value="${val}">`;
                    }
                }

                group.innerHTML = fieldHtml;
                secCard.appendChild(group);
            });

            container.appendChild(secCard);
        });
    }

    // 4. Handle Submit Form
    document.getElementById('dynamicForm').addEventListener('submit', async (e) => {
        e.preventDefault();

        const btn = document.getElementById('btnSubmit');
        btn.disabled = true;
        btn.innerText = 'Mengirim data...';

        const answersPayload = [];

        formSchema.forEach(section => {
            section.payloads.forEach(field => {
                let val = null;

                if (field.type === 'radio_button') {
                    const selected = document.querySelector(`input[name="field_${field.id}"]:checked`);
                    if (selected) {
                        const opt = field.options.find(o => o.id === selected.value);
                        val = opt ? [{ id: opt.id, label: opt.label, value: opt.value || '', parent_id: field.id }] : [];
                    } else {
                        val = [];
                    }
                } else if (field.type === 'checkbox') {
                    const checked = document.querySelectorAll(`input[name="field_${field.id}"]:checked`);
                    val = Array.from(checked).map(cb => cb.value);
                } else if (field.type === 'long_text') {
                    const el = document.querySelector(`textarea[name="field_${field.id}"]`);
                    val = el ? el.value : '';
                } else if (field.type === 'text') {
                    const el = document.querySelector(`input[name="field_${field.id}"]`);
                    val = el ? el.value : '';
                }

                answersPayload.push({
                    field_id: field.id,
                    value: val
                });
            });
        });

        const requestBody = {
            title: document.getElementById('submissionTitle').value || 'Submission dari Web Frontend',
            answers: answersPayload
        };

        try {
            const response = await fetch(`${API_BASE}/submissions`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                },
                body: JSON.stringify(requestBody)
            });

            const result = await response.json();

            if (response.ok && (result.success || result.status === 'success')) {
                showAlert(`Sukses! Form berhasil disimpan ke database. (ID: ${result.data.submission_id})`, 'success', 4000);
                window.scrollTo({ top: 0, behavior: 'smooth' });
            } else {
                throw new Error(result.message || 'Terjadi kesalahan saat submit form.');
            }
        } catch (err) {
            showAlert(`Gagal: ${err.message}`, 'danger');
            window.scrollTo({ top: 0, behavior: 'smooth' });
        } finally {
            btn.disabled = false;
            btn.innerText = 'Kirim Form (Submit to API)';
        }
    });

    function toggleUploadBox() {
        const box = document.getElementById('uploadBox');
        box.style.display = box.style.display === 'none' ? 'block' : 'none';
    }

    function showLoading(show) {
        document.getElementById('loadingBox').style.display = show ? 'block' : 'none';
    }

    function showAlert(message, type, duration = 4000) {
        const alertBox = document.getElementById('alertBox');
        alertBox.className = `alert alert-${type}`;
        alertBox.innerText = message;
        alertBox.style.display = 'block';

        // Auto dismiss setelah durasi (default 4 detik)
        if (alertTimer) clearTimeout(alertTimer);
        if (duration > 0) {
            alertTimer = setTimeout(() => {
                alertBox.style.display = 'none';
            }, duration);
        }
    }
</script>

</body>
</html>

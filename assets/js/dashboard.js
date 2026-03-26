(function () {
  'use strict';

  function readDashboardConfig() {
  if (window.DASHBOARD_CONFIG && typeof window.DASHBOARD_CONFIG === 'object') {
    return window.DASHBOARD_CONFIG;
  }

  const jsonNode = document.getElementById('dashboardConfigJson');
  if (jsonNode) {
    try {
      const parsed = JSON.parse(jsonNode.textContent || '{}');
      if (parsed && typeof parsed === 'object') {
        return parsed;
      }
    } catch (error) {
      console.error('dashboard config parse failed:', error);
    }
  }

  const currentPath = window.location.pathname || '';
  const basePath = currentPath.replace(/\/index\.php$/i, '').replace(/\/$/, '');
  return {
    filtersEndpoint: basePath + '/api/dashboard_filters.php',
    summaryEndpoint: basePath + '/api/dashboard_summary.php',
    populationEndpoint: basePath + '/api/population.php',
    detailEndpoint: basePath + '/api/dashboard_detail.php',
    drilldownEndpoint: basePath + '/api/drilldown.php',
    exportEndpoint: basePath + '/api/export.php',
    monitoringEndpoint: basePath + '/api/monitoring.php',
    maxDateRangeDays: 370,
    defaultStartDate: '',
    defaultEndDate: ''
  };
}

  const cfg = readDashboardConfig();
  const form = document.getElementById('filterForm');
  const errorBox = document.getElementById('errorBox');
  const resetButton = document.getElementById('resetFilter');
  const lastUpdated = document.getElementById('lastUpdated');
  const populationNote = document.getElementById('populationNote');
  const loadingOverlay = document.getElementById('loadingOverlay');
  const loadingText = document.getElementById('loadingText');
  const printDashboardButton = document.getElementById('printDashboard');
  const drilldownModal = document.getElementById('drilldownModal');
  const drilldownTitle = document.getElementById('drilldownTitle');
  const drilldownSubtitle = document.getElementById('drilldownSubtitle');
  const drilldownContent = document.getElementById('drilldownContent');
  const closeDrilldownButton = document.getElementById('closeDrilldown');
  const exportDrilldownCsvButton = document.getElementById('exportDrilldownCsv');
  const exportDrilldownExcelButton = document.getElementById('exportDrilldownExcel');
  const printDrilldownButton = document.getElementById('printDrilldown');
  const etlAlertBox = document.getElementById('etlAlertBox');
  const etlOverviewCards = document.getElementById('etlOverviewCards');
  let loadingDepth = 0;
  let currentDrilldownKey = '';
  let currentDrilldownLabel = '';

  if (!cfg.summaryEndpoint || !cfg.populationEndpoint || !cfg.detailEndpoint) {
    showError('ไม่พบการตั้งค่า endpoint ของแดชบอร์ด');
    return;
  }

  function setFormDisabled(disabled) {
    if (!form) return;
    form.querySelectorAll('button, input, select').forEach((node) => {
      node.disabled = disabled;
    });
  }

  function showLoading(message) {
    loadingDepth += 1;
    if (loadingText) {
      loadingText.textContent = message || 'กรุณารอสักครู่ ระบบกำลังประมวลผลและดึงข้อมูลขึ้นหน้าจอ';
    }
    if (loadingOverlay) {
      loadingOverlay.classList.remove('hidden');
    }
    document.body.classList.add('is-loading');
    setFormDisabled(true);
  }

  function hideLoading() {
    loadingDepth = Math.max(loadingDepth - 1, 0);
    if (loadingDepth > 0) return;
    if (loadingOverlay) {
      loadingOverlay.classList.add('hidden');
    }
    document.body.classList.remove('is-loading');
    setFormDisabled(false);
  }

  function showError(message) {
    if (!errorBox) return;
    errorBox.textContent = message || 'เกิดข้อผิดพลาด';
    errorBox.classList.remove('hidden');
  }

  function hideError() {
    if (!errorBox) return;
    errorBox.textContent = '';
    errorBox.classList.add('hidden');
  }

  function numberFormat(value, digits = 0) {
    return new Intl.NumberFormat('th-TH', {
      minimumFractionDigits: digits,
      maximumFractionDigits: digits,
    }).format(Number(value || 0));
  }

  function formatDuration(seconds) {
    const total = Number(seconds || 0);
    if (!Number.isFinite(total) || total <= 0) {
      return '0 วินาที';
    }
    if (total < 60) {
      return numberFormat(total) + ' วินาที';
    }
    if (total < 3600) {
      return numberFormat(total / 60, 2) + ' นาที';
    }
    return numberFormat(total / 3600, 2) + ' ชั่วโมง';
  }

  function escapeHtml(value) {
    return String(value == null ? '' : value)
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;')
      .replace(/'/g, '&#039;');
  }

  function getQueryString() {
    const params = new URLSearchParams();
    if (!form) return params.toString();

    form.querySelectorAll('input[name], select[name], textarea[name]').forEach((node) => {
      const key = node.getAttribute('name');
      if (!key) return;
      const value = String(node.value || '').trim();
      if (value !== '') {
        params.set(key, value);
      }
    });

    return params.toString();
  }

  function validateDateRange() {
    const start = form.start_date.value;
    const end = form.end_date.value;
    if (!start || !end) {
      throw new Error('กรุณาระบุวันที่เริ่มต้นและวันที่สิ้นสุด');
    }
    const startDate = new Date(start + 'T00:00:00');
    const endDate = new Date(end + 'T00:00:00');
    if (startDate > endDate) {
      throw new Error('วันที่เริ่มต้นต้องไม่มากกว่าวันที่สิ้นสุด');
    }
    const diffDays = Math.floor((endDate - startDate) / 86400000);
    if (cfg.maxDateRangeDays && diffDays > Number(cfg.maxDateRangeDays)) {
      throw new Error('ช่วงวันที่ต้องไม่เกิน ' + cfg.maxDateRangeDays + ' วัน');
    }
  }

  async function fetchJson(url, retries = 1) {
    let response;

    try {
      response = await fetch(url, {
        headers: { Accept: 'application/json' },
        credentials: 'same-origin',
      });
    } catch (error) {
      if (retries > 0) {
        await new Promise((resolve) => setTimeout(resolve, 400));
        return fetchJson(url, retries - 1);
      }
      throw new Error('เรียก API ไม่สำเร็จ: ' + url);
    }

    let payload = null;
    try {
      payload = await response.json();
    } catch (error) {
      throw new Error('รูปแบบข้อมูลตอบกลับไม่ถูกต้อง: ' + url);
    }

    if (!response.ok || !payload || payload.status === false || payload.success === false) {
      const message = (payload && payload.message) || ('HTTP ' + response.status + ' จาก ' + url);
      if (response.status >= 500 && retries > 0) {
        await new Promise((resolve) => setTimeout(resolve, 400));
        return fetchJson(url, retries - 1);
      }
      throw new Error(message);
    }

    return payload.data || {};
  }

  function metricDigits(key) {
    if (key === 'ipd_ot_sum') {
      return 2;
    }
    if (['ipd_avg_rw', 'ipd_sum_adjrw', 'ipd_cmi'].includes(key)) {
      return 4;
    }
    return 0;
  }

  function setMetricValues(data) {
    document.querySelectorAll('[data-key]').forEach((node) => {
      const key = node.getAttribute('data-key');
      const hasValue = Object.prototype.hasOwnProperty.call(data, key);
      const rawValue = hasValue ? data[key] : 0;
      node.textContent = numberFormat(rawValue ?? 0, metricDigits(key));
    });
  }

  function setPopulationValues(data) {
    document.querySelectorAll('[data-population-key]').forEach((node) => {
      const key = node.getAttribute('data-population-key');
      node.textContent = numberFormat(data[key] || 0);
    });
    if (populationNote && data.source_note) {
      populationNote.textContent = data.source_note;
    }
  }


  function claimMetricDigits(key) {
    return key === 'total_visit' ? 0 : 2;
  }

  function setClaimValues(data) {
    document.querySelectorAll('[data-claim-key]').forEach((node) => {
      const key = node.getAttribute('data-claim-key');
      const hasValue = Object.prototype.hasOwnProperty.call(data || {}, key);
      const rawValue = hasValue ? data[key] : 0;
      node.textContent = numberFormat(rawValue ?? 0, claimMetricDigits(key));
    });
  }

  function renderTable(targetId, columns, rows, emptyText = 'ไม่พบข้อมูล') {
    const root = document.getElementById(targetId);
    if (!root) return;
    if (!Array.isArray(rows) || rows.length === 0) {
      root.innerHTML = '<div class="empty-state">' + escapeHtml(emptyText) + '</div>';
      return;
    }

    const thead = columns.map((column) => `<th class="${column.numeric ? 'num' : ''}">${escapeHtml(column.label)}</th>`).join('');
    const tbody = rows.map((row) => {
      const cells = columns.map((column) => {
        const raw = row[column.key];
        const value = column.formatter ? column.formatter(raw, row) : escapeHtml(raw ?? '');
        return `<td class="${column.numeric ? 'num' : ''}">${value}</td>`;
      }).join('');
      return `<tr>${cells}</tr>`;
    }).join('');

    root.innerHTML = `<div class="data-table-wrap"><table class="data-table"><thead><tr>${thead}</tr></thead><tbody>${tbody}</tbody></table></div>`;
  }

  function renderChart(containerId, optionsBuilder) {
    const node = document.getElementById(containerId);
    if (!node) return;
    if (!window.Highcharts) {
      node.innerHTML = '<div class="empty-state">ไม่พบ Highcharts ที่โฟลเดอร์ assets/highcharts/js/highcharts.js</div>';
      return;
    }
    const options = optionsBuilder();
    options.accessibility = { enabled: false };
    window.Highcharts.chart(containerId, options);
  }

  function renderDetail(data) {
    const charts = data.charts || {};
    const tables = data.tables || {};

    renderChart('opdMonthlyChart', () => ({
      chart: { type: 'column' },
      title: { text: null },
      xAxis: { categories: (charts.opd_monthly_service && charts.opd_monthly_service.categories) || [] },
      yAxis: { title: { text: 'จำนวนครั้ง' } },
      credits: { enabled: false },
      series: (charts.opd_monthly_service && charts.opd_monthly_service.series) || [],
    }));

    renderChart('ipdMonthlyChart', () => ({
      chart: { type: 'column' },
      title: { text: null },
      xAxis: { categories: (charts.ipd_monthly_service && charts.ipd_monthly_service.categories) || [] },
      yAxis: { title: { text: 'จำนวน AN' } },
      credits: { enabled: false },
      series: (charts.ipd_monthly_service && charts.ipd_monthly_service.series) || [],
    }));

    renderChart('rightsMonthlyChart', () => ({
      chart: { type: 'column' },
      title: { text: null },
      xAxis: { categories: (charts.rights_monthly_service && charts.rights_monthly_service.categories) || [] },
      yAxis: { min: 0, title: { text: 'จำนวนครั้ง' } },
      plotOptions: { column: { stacking: 'normal' } },
      credits: { enabled: false },
      series: (charts.rights_monthly_service && charts.rights_monthly_service.series) || [],
    }));

    renderChart('rightsInAreaChart', () => ({
      chart: { type: 'pie' },
      title: { text: null },
      plotOptions: { pie: { innerSize: '60%', dataLabels: { enabled: true } } },
      credits: { enabled: false },
      series: [{
        name: 'จำนวนคน',
        data: charts.rights_in_area_distribution || [],
      }],
    }));

    const diseaseColumns = [
      { key: 'icd10', label: 'ICD10' },
      { key: 'icd_name', label: 'ชื่อโรค' },
      { key: 'total_count', label: 'จำนวนครั้ง', numeric: true, formatter: (value) => numberFormat(value || 0) },
      { key: 'percent', label: '%', numeric: true, formatter: (value) => numberFormat(value || 0, 2) },
    ];

    renderTable('villagePopulationTable', [
      { key: 'village_moo', label: 'หมู่' },
      { key: 'village_name', label: 'หมู่บ้าน' },
      { key: 'total_population', label: 'ประชากรรวม', numeric: true, formatter: (value) => numberFormat(value || 0) },
      { key: 'in_area_population', label: 'คนในเขต', numeric: true, formatter: (value) => numberFormat(value || 0) },
    ], tables.village_population_summary || []);

    renderTable('opdDiseasesTable', diseaseColumns, tables.opd_diseases_top10 || []);
    renderTable('ipdDiseasesTable', diseaseColumns, tables.ipd_diseases_top10 || []);
    renderTable('chronicDiseasesTable', diseaseColumns, tables.chronic_opd_diseases_top10 || []);

    renderTable('clinicChargesTable', [
      { key: 'clinic_name', label: 'คลินิก / จุดบริการ' },
      { key: 'total_income', label: 'ค่าใช้จ่ายรวม', numeric: true, formatter: (value) => numberFormat(value || 0, 2) },
      { key: 'total_paid_money', label: 'จ่ายจริง', numeric: true, formatter: (value) => numberFormat(value || 0, 2) },
      { key: 'total_diff', label: 'ส่วนต่าง', numeric: true, formatter: (value) => numberFormat(value || 0, 2) },
    ], tables.clinic_charges_top10 || []);

    renderTable('authClinicTable', [
      { key: 'clinic_name', label: 'คลินิก / จุดบริการ' },
      { key: 'verified_count', label: 'ยืนยันตัวตน', numeric: true, formatter: (value) => numberFormat(value || 0) },
      { key: 'not_verified_count', label: 'ไม่ยืนยันตัวตน', numeric: true, formatter: (value) => numberFormat(value || 0) },
    ], tables.auth_clinics_top10 || []);

    const claim = data.claim || {};
    const claimCharts = claim.charts || {};
    const claimTables = claim.tables || {};

    setClaimValues(claim.cards || {});

    const claimNote = document.getElementById('claimNote');
    if (claimNote && claim.note) {
      claimNote.textContent = claim.note;
    }

    renderChart('claimMonthlyChart', () => ({
      chart: { type: 'column' },
      title: { text: null },
      xAxis: { categories: (claimCharts.claim_monthly_summary && claimCharts.claim_monthly_summary.categories) || [] },
      yAxis: { min: 0, title: { text: 'บาท' } },
      credits: { enabled: false },
      series: (claimCharts.claim_monthly_summary && claimCharts.claim_monthly_summary.series) || [],
    }));

    renderChart('claimBurdenChart', () => ({
      chart: { type: 'line' },
      title: { text: null },
      xAxis: { categories: (claimCharts.claim_burden_comparison && claimCharts.claim_burden_comparison.categories) || [] },
      yAxis: { title: { text: 'บาท' } },
      credits: { enabled: false },
      series: (claimCharts.claim_burden_comparison && claimCharts.claim_burden_comparison.series) || [],
    }));

    renderTable('claimStatusTable', [
      { key: 'claim_status_group', label: 'กลุ่มสถานะ' },
      { key: 'visit_count', label: 'จำนวนบริการ', numeric: true, formatter: (value) => numberFormat(value || 0) },
      { key: 'total_charge', label: 'ค่าใช้จ่ายรวม', numeric: true, formatter: (value) => numberFormat(value || 0, 2) },
      { key: 'claim_amount', label: 'ยอดเคลม', numeric: true, formatter: (value) => numberFormat(value || 0, 2) },
      { key: 'hospital_burden_after_claim', label: 'ภาระหลังหักเคลม', numeric: true, formatter: (value) => numberFormat(value || 0, 2) },
    ], claimTables.claim_status_summary || []);

    renderTable('claimMonthlyTable', [
      { key: 'month_key', label: 'เดือน' },
      { key: 'total_visit', label: 'บริการ', numeric: true, formatter: (value) => numberFormat(value || 0) },
      { key: 'total_charge', label: 'ค่าใช้จ่ายรวม', numeric: true, formatter: (value) => numberFormat(value || 0, 2) },
      { key: 'patient_paid_total', label: 'เงินสด', numeric: true, formatter: (value) => numberFormat(value || 0, 2) },
      { key: 'settled_claim_amount', label: 'โอนแล้ว', numeric: true, formatter: (value) => numberFormat(value || 0, 2) },
      { key: 'hospital_burden_after_claim', label: 'ภาระหลังหักเคลม', numeric: true, formatter: (value) => numberFormat(value || 0, 2) },
    ], claimTables.claim_monthly_summary || []);

    renderTable('claimSettledTable', [
      { key: 'month_key', label: 'เดือน' },
      { key: 'settled_visit_count', label: 'บริการโอนแล้ว', numeric: true, formatter: (value) => numberFormat(value || 0) },
      { key: 'settled_total_charge', label: 'ค่าใช้จ่ายรวม', numeric: true, formatter: (value) => numberFormat(value || 0, 2) },
      { key: 'settled_claim_received', label: 'เคลมได้รับ', numeric: true, formatter: (value) => numberFormat(value || 0, 2) },
      { key: 'settled_balance_after_claim', label: 'ส่วนต่างคงเหลือ', numeric: true, formatter: (value) => numberFormat(value || 0, 2) },
    ], claimTables.claim_settled_finance || []);
  }

  function buildExportUrl(scope, key, format) {
    const params = new URLSearchParams(getQueryString());
    params.set('scope', scope);
    params.set('format', format);
    if (scope === 'drilldown') {
      params.set('metric', key);
    } else if (scope === 'table') {
      params.set('table', key);
    }
    return cfg.exportEndpoint + '?' + params.toString();
  }

  function downloadExport(scope, key, format) {
    window.location.href = buildExportUrl(scope, key, format);
  }

  function setDrilldownButtonsState(disabled) {
    [exportDrilldownCsvButton, exportDrilldownExcelButton, printDrilldownButton].forEach((button) => {
      if (button) button.disabled = disabled;
    });
  }

  function openModal() {
    if (!drilldownModal) return;
    drilldownModal.classList.remove('hidden');
    document.body.classList.add('modal-open');
    drilldownModal.setAttribute('aria-hidden', 'false');
  }

  function closeModal() {
    if (!drilldownModal) return;
    drilldownModal.classList.add('hidden');
    document.body.classList.remove('modal-open');
    drilldownModal.setAttribute('aria-hidden', 'true');
  }

  async function openDrilldown(metric, label) {
    if (!cfg.drilldownEndpoint) return;
    currentDrilldownKey = metric;
    currentDrilldownLabel = label;
    if (drilldownTitle) drilldownTitle.textContent = label || 'รายละเอียด';
    if (drilldownSubtitle) drilldownSubtitle.textContent = 'กำลังโหลดข้อมูล...';
    if (drilldownContent) drilldownContent.innerHTML = '<div class="empty-state">กำลังโหลดข้อมูล...</div>';
    setDrilldownButtonsState(true);
    openModal();

    try {
      const params = new URLSearchParams(getQueryString());
      params.set('metric', metric);
      const data = await fetchJson(cfg.drilldownEndpoint + '?' + params.toString());
      if (drilldownSubtitle) {
        drilldownSubtitle.textContent = (data.summary && Object.keys(data.summary).length > 0)
          ? Object.entries(data.summary).map(([key, value]) => `${key}: ${value}`).join(' | ')
          : 'แสดงข้อมูลสูงสุด 1,000 แถว';
      }
      const columns = data.columns || [];
      const rows = data.rows || [];
      if (!drilldownContent) return;
      if (!Array.isArray(rows) || rows.length === 0) {
        drilldownContent.innerHTML = '<div class="empty-state">ไม่พบข้อมูล</div>';
      } else {
        const thead = columns.map((column) => `<th>${escapeHtml(column.label || column.key)}</th>`).join('');
        const tbody = rows.map((row) => `<tr>${columns.map((column) => `<td>${escapeHtml(row[column.key] ?? '')}</td>`).join('')}</tr>`).join('');
        drilldownContent.innerHTML = `<div class="data-table-wrap"><table class="data-table"><thead><tr>${thead}</tr></thead><tbody>${tbody}</tbody></table></div>`;
      }
      setDrilldownButtonsState(false);
    } catch (error) {
      if (drilldownSubtitle) drilldownSubtitle.textContent = 'เกิดข้อผิดพลาด';
      if (drilldownContent) drilldownContent.innerHTML = `<div class="empty-state">${escapeHtml(error.message || 'เกิดข้อผิดพลาด')}</div>`;
    }
  }

  function renderMonitoring(data) {
    const overview = data.overview || {};
    const alertActive = overview.alert_active === true;
    if (etlAlertBox) {
      etlAlertBox.className = 'alert ' + (alertActive ? 'error' : 'success');
      etlAlertBox.classList.remove('hidden');
      etlAlertBox.textContent = alertActive
        ? ('พบความผิดปกติ ETL: status ล่าสุด = ' + (overview.latest_status || '-') + ' / fail 24h = ' + numberFormat(overview.failed_count_24h || 0))
        : 'ETL ปกติ: status ล่าสุด = SUCCESS';
    }

    if (etlOverviewCards) {
      etlOverviewCards.innerHTML = [
        { label: 'ชื่องานล่าสุด', value: overview.latest_job_name || '-' },
        { label: 'สถานะล่าสุด', value: overview.latest_status || '-' },
        { label: 'ระยะเวลา', value: formatDuration(overview.latest_duration_seconds || 0) },
        { label: 'กำลังรัน', value: numberFormat(overview.running_count || 0) },
        { label: 'Fail 24 ชม.', value: numberFormat(overview.failed_count_24h || 0) },
        { label: 'ข้อความล่าสุด', value: overview.last_message || '-' },
      ].map((item) => `<div class="monitor-card"><div class="monitor-label">${escapeHtml(item.label)}</div><div class="monitor-value">${escapeHtml(item.value)}</div></div>`).join('');
    }

    renderTable('etlRunsTable', [
      { key: 'job_name', label: 'Job' },
      { key: 'status', label: 'Status' },
      { key: 'started_at', label: 'Started' },
      { key: 'finished_at', label: 'Finished' },
      { key: 'duration_seconds', label: 'Duration(s)', numeric: true, formatter: (value) => numberFormat(value || 0) },
      { key: 'rows_affected', label: 'Rows', numeric: true, formatter: (value) => numberFormat(value || 0) },
      { key: 'message', label: 'Message' },
    ], data.recent_runs || []);

    renderTable('auditLogTable', [
      { key: 'created_at', label: 'เวลา' },
      { key: 'action', label: 'Action' },
      { key: 'status', label: 'Status' },
      { key: 'user_ip', label: 'IP' },
      { key: 'request_method', label: 'Method' },
      { key: 'request_uri', label: 'URI' },
      { key: 'message', label: 'Message' },
    ], data.audit_recent || [], 'ยังไม่มี audit log หรือยังไม่ได้สร้างตาราง web_audit_log');
  }

  async function loadFilters() {
    showLoading('กำลังโหลดตัวกรองข้อมูล');
    try {
      const data = await fetchJson(cfg.filtersEndpoint);
      const clinic = document.getElementById('clinic');
      const rights = document.getElementById('rights');
      if (clinic) {
        clinic.innerHTML = '<option value="">ทั้งหมด</option>' + (data.clinics || []).map((item) => `<option value="${escapeHtml(item.value)}">${escapeHtml(item.label)}</option>`).join('');
      }
      if (rights) {
        rights.innerHTML = '<option value="">ทั้งหมด</option>' + (data.rights || []).map((item) => `<option value="${escapeHtml(item.value)}">${escapeHtml(item.label)}</option>`).join('');
      }
    } finally {
      hideLoading();
    }
  }

  async function loadMonitoring() {
    const data = await fetchJson(cfg.monitoringEndpoint);
    renderMonitoring(data || {});
  }

  async function loadAll() {
    hideError();
    validateDateRange();
    const query = getQueryString();

    showLoading('กำลังโหลดข้อมูลแดชบอร์ด');
    try {
      const summaryUrl = cfg.summaryEndpoint + (query ? ('?' + query) : '');
      const populationUrl = cfg.populationEndpoint;
      const detailUrl = cfg.detailEndpoint + (query ? ('?' + query) : '');
      const monitoringUrl = cfg.monitoringEndpoint;
      const errors = [];

      try {
        const summary = await fetchJson(summaryUrl);
        setMetricValues(summary || {});
        if (lastUpdated) {
          lastUpdated.textContent = (summary && summary.last_updated) || '-';
        }
      } catch (error) {
        errors.push('summary: ' + (error.message || 'เกิดข้อผิดพลาด'));
      }

      try {
        const population = await fetchJson(populationUrl);
        setPopulationValues(population || {});
        if (lastUpdated && lastUpdated.textContent === '-') {
          lastUpdated.textContent = (population && population.last_updated) || '-';
        }
      } catch (error) {
        errors.push('population: ' + (error.message || 'เกิดข้อผิดพลาด'));
      }

      try {
        const detail = await fetchJson(detailUrl);
        renderDetail(detail || {});
        if (lastUpdated && lastUpdated.textContent === '-') {
          lastUpdated.textContent = (detail && detail.last_updated) || '-';
        }
      } catch (error) {
        errors.push('detail: ' + (error.message || 'เกิดข้อผิดพลาด'));
      }

      if (monitoringUrl) {
        try {
          const monitoring = await fetchJson(monitoringUrl);
          renderMonitoring(monitoring || {});
        } catch (error) {
          errors.push('monitoring: ' + (error.message || 'เกิดข้อผิดพลาด'));
        }
      }

      if (errors.length > 0) {
        showError(errors.join(' | '));
      }
    } finally {
      hideLoading();
    }
  }

  function resetForm() {
    form.start_date.value = cfg.defaultStartDate || '';
    form.end_date.value = cfg.defaultEndDate || '';
    form.clinic.value = '';
    form.rights.value = '';
    form.patient_type.value = '';
  }

  form.addEventListener('submit', async function (event) {
    event.preventDefault();
    try {
      await loadAll();
    } catch (error) {
      showError(error.message || 'เกิดข้อผิดพลาด');
    }
  });

  resetButton.addEventListener('click', async function () {
    resetForm();
    try {
      await loadAll();
    } catch (error) {
      showError(error.message || 'เกิดข้อผิดพลาด');
    }
  });

  if (printDashboardButton) {
    printDashboardButton.addEventListener('click', function () {
      window.print();
    });
  }

  document.querySelectorAll('[data-export-scope]').forEach((button) => {
    button.addEventListener('click', function () {
      const scope = button.getAttribute('data-export-scope') || 'summary';
      const format = button.getAttribute('data-export-format') || 'csv';
      const key = button.getAttribute('data-export-key') || button.getAttribute('data-export-metric') || 'summary';
      downloadExport(scope, key, format);
    });
  });

  document.querySelectorAll('.metric-card.drillable').forEach((card) => {
    card.addEventListener('click', function () {
      const metric = card.getAttribute('data-drill-key');
      const label = card.getAttribute('data-drill-label') || metric;
      if (!metric) return;
      openDrilldown(metric, label);
    });
  });

  if (closeDrilldownButton) {
    closeDrilldownButton.addEventListener('click', closeModal);
  }
  if (drilldownModal) {
    drilldownModal.addEventListener('click', function (event) {
      const target = event.target;
      if (target && target.getAttribute('data-close-modal') === '1') {
        closeModal();
      }
    });
  }
  document.addEventListener('keydown', function (event) {
    if (event.key === 'Escape' && drilldownModal && !drilldownModal.classList.contains('hidden')) {
      closeModal();
    }
  });

  if (exportDrilldownCsvButton) {
    exportDrilldownCsvButton.addEventListener('click', function () {
      if (!currentDrilldownKey) return;
      downloadExport('drilldown', currentDrilldownKey, 'csv');
    });
  }
  if (exportDrilldownExcelButton) {
    exportDrilldownExcelButton.addEventListener('click', function () {
      if (!currentDrilldownKey) return;
      downloadExport('drilldown', currentDrilldownKey, 'excel');
    });
  }
  if (printDrilldownButton) {
    printDrilldownButton.addEventListener('click', function () {
      if (!drilldownContent) return;
      const popup = window.open('', '_blank', 'width=1100,height=800');
      if (!popup) return;
      popup.document.write('<html><head><meta charset="UTF-8"><title>' + escapeHtml(currentDrilldownLabel || 'รายละเอียด') + '</title>');
      popup.document.write('<style>body{font-family:Tahoma,Arial,sans-serif;padding:16px;} table{width:100%;border-collapse:collapse;} th,td{border:1px solid #ccc;padding:8px;text-align:left;} th{background:#f4f6f8;} </style>');
      popup.document.write('</head><body><h2>' + escapeHtml(currentDrilldownLabel || 'รายละเอียด') + '</h2>' + drilldownContent.innerHTML + '</body></html>');
      popup.document.close();
      popup.focus();
      popup.print();
    });
  }

  (async function init() {
    try {
      await loadFilters();
      await loadAll();
    } catch (error) {
      showError(error.message || 'เกิดข้อผิดพลาด');
    }
  })();
})();

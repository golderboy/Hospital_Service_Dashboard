(function(){
  const $tbl = $('#tbl');

  // อ่านค่าการเรียงและตัวจับเวลาแบบที่คุณทำไว้ (config.js หรือ data-attributes)
  const order = window.__DT_ORDER__ || JSON.parse($tbl.attr('data-order') || '[[0,"desc"]]');
  const secondsLeft = window.__SECONDS_LEFT__ || parseInt($tbl.attr('data-seconds-left') || '0', 10);

  // ชื่อไฟล์ export
  function ts(){
    const d = new Date();
    const pad = n => String(n).padStart(2,'0');
    return `${d.getFullYear()}${pad(d.getMonth()+1)}${pad(d.getDate())}_${pad(d.getHours())}${pad(d.getMinutes())}`;
  }

  const table = $tbl.DataTable({
    pageLength: 25,
    stateSave: true,
    order: order,
    dom: '<"top"Bf>rt<"bottom"lip><"clear">',
    lengthMenu: [[25, 50, 100, -1], [25, 50, 100, 'ทั้งหมด']], // <<-- เพิ่ม All
    buttons: window.__DT_BUTTONS__ || [],                       // ถ้ามีปุ่ม export
    buttons: [{
      extend: 'excelHtml5',
      text: 'ส่งออก Excel',
      filename: 'smh_opd_audit_' + ts(),
      title: null,                         // ไม่ใส่หัวเรื่องในไฟล์
      exportOptions: {
        columns: ':visible'                // เฉพาะคอลัมน์ที่แสดง (แฟล็กที่ซ่อนจะไม่ถูกส่งออก)
      }
    }],
    language: {
      search: "ค้นหา:",
      lengthMenu: "แสดง _MENU_ แถว",
      info: "แสดง _START_ ถึง _END_ จากทั้งหมด _TOTAL_ แถว",
      infoEmpty: "ไม่มีข้อมูล",
      zeroRecords: "ไม่พบรายการที่ค้นหา",
      paginate: { first:"หน้าแรก", last:"หน้าสุดท้าย", next:"ถัดไป", previous:"ก่อนหน้า" }
    }
  });



  // กะพริบ title เมื่อถึงเวลาประมวลผลรอบถัดไป
  const title = document.title || 'SMH OPD Audit';
  if (secondsLeft <= 0) {
    let on = false;
    setInterval(()=>{ document.title = on ? title : "⚠ ถึงเวลาประมวลผลแล้ว"; on = !on; }, 1000);
  }
})();

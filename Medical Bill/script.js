let companies = [];
let sales = [];

function loadCompanyDetails(company) {
  const cnameParts = company.cname.split(',');
  document.getElementById('pharmacyName').textContent = (cnameParts[0] || '').toUpperCase();
  document.getElementById('clinicName').textContent = cnameParts[1] ? cnameParts[1].trim() : '';
  document.getElementById('addressLine').textContent = `${company.address}, ${company.city} - ${company.pin}`;
  document.getElementById('contactLine').textContent = `Cell: ${company.mno}, Phone: ${company.landline || '-'}`;
  document.getElementById('gstLine').textContent = `DL No: ${company.dlno1}, GST NO: ${company.gst}`;
}

function loadBillDetails(companyId, billNo) {
  const company = companies.find(c => c.id === companyId);
  if (!company) {
    alert('Company not found.');
    return;
  }

  const salesItems = sales.filter(s => s.no === billNo && s.status === 'a');

  if (salesItems.length === 0) {
    alert('No matching sales found for this bill number and company.');
    return;
  }

  loadCompanyDetails(company);

  const firstSaleItem = salesItems[0];
  document.getElementById('billNo').textContent = firstSaleItem.bno || '-';
  document.getElementById('billMs').textContent = firstSaleItem.cname || '-';

  const issuedateParts = firstSaleItem.issuedate.split('-');
  const formattedDate = `${issuedateParts[2]}/${issuedateParts[1]}/${issuedateParts[0]}`;
  document.getElementById('billDate').textContent = formattedDate;

  document.getElementById('doctorName').textContent = firstSaleItem.empname || '-';

  const tbody = document.getElementById('itemRows');
  tbody.innerHTML = '';
  let total = 0;

  salesItems.forEach((item, i) => {
    const amount = item.qty * item.rate;
    total += amount;
    const row = document.createElement('tr');
    row.innerHTML = `
      <td>${i + 1}</td>
      <td>${item.pname}</td>
      <td>${item.bno || '-'}</td>
      <td>${item.exp || '-'}</td>
      <td>${item.qty}</td>
      <td>${item.rate.toFixed(2)}</td>
      <td>${amount.toFixed(2)}</td>
    `;
    tbody.appendChild(row);
  });

  document.getElementById('totalAmt').textContent = Number(firstSaleItem.g_total).toFixed(2);
  document.getElementById('netAmt').textContent = Number(firstSaleItem.g_total).toFixed(2);
}

window.onload = async function () {
  const urlParams = new URLSearchParams(window.location.search);
  const billNoParam = urlParams.get('ino');
  const companyIdParam = urlParams.get('company');

  if (!billNoParam || !companyIdParam) {
    alert('Missing URL parameters: ino or company');
    return;
  }

  [companies, sales] = await Promise.all([
    fetch('company.json').then(res => res.json()),
    fetch('sales.json').then(res => res.json())
  ]);

  loadBillDetails(Number(companyIdParam), billNoParam);
};

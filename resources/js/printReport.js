import { afn, statusLabel } from './format';
import { t } from './i18n';function cell(value, extra = '') {
    return `<td style="padding:7px 10px;border-bottom:1px solid #e8e3d8;${extra}">${value ?? ''}</td>`;
}


function table(headers, rows) {
    const head = headers.map((header) => `<th style="padding:7px 10px;text-align:start;background:#f2efe9;color:#6b6659;border-bottom:1px solid #dcd6c9;font-size:11px;letter-spacing:.12em;text-transform:uppercase;font-weight:700;">${header}</th>`).join('');
    const body = rows.map((row) => `<tr>${row.map((item) => cell(item.value, item.style || '')).join('')}</tr>`).join('');

    return `<table style="width:100%;border-collapse:collapse;margin:8px 0 22px;font-size:14px;"><thead><tr>${head}</tr></thead><tbody>${body}</tbody></table>`;
}

export function printReport({ report, schoolName, today }) {
    const dir = document.documentElement.dir || 'ltr';
    const lang = document.documentElement.lang || 'en';
    const year = report.year;
    const monthly = table(
        [t('reports.month'), t('reports.income'), t('reports.teacherSalaries'), t('reports.otherExpenses'), t('reports.totalExpenses'), t('reports.balance')],
        report.monthly.map((row) => [
            { value: row.name, style: 'font-weight:700;' },
            { value: afn(row.income), style: 'color:#1c6b3f;' },
            { value: afn(row.teacher_salaries), style: 'color:#9d2b20;' },
            { value: afn(row.other_expenses), style: 'color:#9d2b20;' },
            { value: afn(row.expenses), style: 'color:#9d2b20;' },
            { value: afn(row.balance), style: 'font-weight:700;' },
        ]),
    );
    const byClass = table(
        [t('reports.class'), t('reports.payments'), t('reports.income')],
        (report.income.by_class || []).map((row) => [
            { value: row.class },
            { value: row.payments },
            { value: afn(row.total), style: 'color:#1c6b3f;' },
        ]),
    );
    const outstanding = table(
        [t('fees.student'), t('fees.class'), t('reports.unpaidMonths'), t('reports.monthlyFee'), t('reports.totalOutstanding')],
        report.outstanding.map((row) => [
            { value: row.student },
            { value: row.class },
            { value: row.unpaid_months.map((month) => month.name).join('، ') },
            { value: afn(row.monthly_fee) },
            { value: afn(row.total_outstanding), style: 'color:#8a5a10;font-weight:700;' },
        ]),
    );
    const salaries = table(
        [t('reports.teacher'), t('reports.month'), t('reports.salary'), t('status.paid'), t('reports.paidAmount')],
        report.salaries.rows.filter((row) => row.status !== 'upcoming').map((row) => [
            { value: row.teacher },
            { value: row.month_name },
            { value: afn(row.salary) },
            { value: statusLabel(row.status) },
            { value: row.status === 'paid' ? afn(row.amount_paid) : '—' },
        ]),
    );
    const expenses = table(
        [t('expenses.category'), t('common.account'), t('reports.month'), t('expenses.description'), t('common.amount')],
        report.expenses.map((row) => [
            { value: row.category },
            { value: row.account },
            { value: row.month_name },
            { value: row.description },
            { value: afn(row.amount), style: 'color:#9d2b20;' },
        ]),
    );

    const html = `<!DOCTYPE html>
<html lang="${lang}" dir="${dir}">
<head>
<meta charset="utf-8">
<title>${t('reports.document')} ${year}</title>
<link href="https://fonts.googleapis.com/css2?family=Amiri:wght@400;700&display=swap" rel="stylesheet">
<style>
  body { margin: 28px; font-family: Amiri, serif; color: #16150f; background: #fff; }
  h1 { margin: 0; font-size: 28px; font-weight: 700; color: #16150f; }
  h2 { margin: 22px 0 6px; font-size: 17px; font-weight: 700; color: #0f5c55; }
  p { margin: 4px 0; }
  .rule { height: 0; border-top: 2px solid #16150f; margin: 10px 0 18px; }
</style>
</head>
<body>
  <p style="letter-spacing:.18em;text-transform:uppercase;font-size:10.5px;font-weight:700;color:#0f5c55;">EduFinance Pro</p>
  <h1>${schoolName}</h1>
  <p>${t('reports.document')} · ${year}${today ? ` · ${today}` : ''}</p>
  <div class="rule"></div>
  <p>${t('reports.totalIncome')}: <strong>${afn(report.income.total)}</strong> · ${t('reports.totalPaid')}: <strong>${afn(report.salaries.total_paid)}</strong></p>
  <h2>${t('reports.monthlyTab')}</h2>
  ${monthly}
  <h2>${t('reports.incomeTab')}</h2>
  ${byClass || ''}
  <h2>${t('reports.outstandingTab')}</h2>
  ${outstanding}
  <h2>${t('reports.salaryTab')}</h2>
  ${salaries}
  <h2>${t('reports.expenseTab')}</h2>
  ${expenses}
</body>
</html>`;

    const frame = document.createElement('iframe');
    frame.style.position = 'fixed';
    frame.style.right = '0';
    frame.style.bottom = '0';
    frame.style.width = '0';
    frame.style.height = '0';
    frame.style.border = '0';
    document.body.appendChild(frame);
    const doc = frame.contentWindow.document;
    doc.open();
    doc.write(html);
    doc.close();
    frame.onload = () => {
        frame.contentWindow.focus();
        frame.contentWindow.print();
        setTimeout(() => frame.remove(), 1000);
    };
}

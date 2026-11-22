import { Head, Link, router, useForm, usePage } from '@inertiajs/react';
import {
    Plus,
    Pencil,
    Check,
    ClipboardCheck,
    SlidersHorizontal,
    Eye,
    PackageCheck,
    Printer,
    Minus,
    X,
    CircleCheck,
} from 'lucide-react';
import { useState, type FormEvent } from 'react';
import { Button } from '../components/ui/button';
import { Dialog } from '../components/ui/dialog';
import {
    Cell,
    Empty,
    Errors,
    PageTitle,
    Pagination,
    SearchBox,
    Status,
    getValue,
} from '../components/common';
import { labels, money, number, type Row, type Shared } from '../lib/utils';

type Field = { key: string; label: string; type: string; rules: string; source?: string };
export default function Records({
    definition: d,
    records,
    options,
    canWrite,
    filters,
}: {
    definition: Row;
    records: Row;
    options: Record<string, Row[]>;
    canWrite: boolean;
    filters: Row;
}) {
    const {
        auth: { user },
    } = usePage<Shared>().props;
    const [q, setQ] = useState(filters.q ?? '');
    const [sort, setSort] = useState(
        typeof filters.sort === 'string' ? filters.sort : 'id',
    );
    const [categoryFilter, setCategoryFilter] = useState(filters.category_id ?? '');
    const [branchFilter, setBranchFilter] = useState(filters.branch_id ?? '');
    const [start, setStart] = useState(filters.start ?? '');
    const [end, setEnd] = useState(filters.end ?? '');
    const [expenseCategory, setExpenseCategory] = useState(filters.category ?? '');
    const [edit, setEdit] = useState<Row | null>(null);
    const [creating, setCreating] = useState(false);
    const [detail, setDetail] = useState<Row | null>(null);
    const [workflow, setWorkflow] = useState<{
        row: Row;
        action: string;
        label: string;
    } | null>(null);
    const form = useForm<Row>({});
    const actionForm = useForm<Row>({});
    const path = `/manage/${d.key}`;
    const immutable = ['recipes', 'purchases', 'transfers', 'price-requests'];
    function openForm(row?: Row) {
        form.clearErrors();
        const values: Row = {};
        (d.fields as Field[]).forEach((f) => {
            values[f.key] =
                f.type === 'checkbox'
                    ? (row?.[f.key] ?? true)
                    : ['recipe', 'purchase'].includes(f.type)
                      ? [
                            {
                                ingredient_id: '',
                                quantity: '',
                                ...(f.type === 'purchase' ? { unit_price: '' } : {}),
                            },
                        ]
                      : f.type === 'password'
                        ? ''
                        : (row?.[f.key] ??
                          (f.key.includes('branch_id') && user.branch_id
                              ? user.branch_id
                              : ''));
            if (f.type === 'time' && typeof values[f.key] === 'string') {
                values[f.key] = values[f.key].slice(0, 5);
            }
        });
        form.setData(values);
        setEdit(row ?? null);
        setCreating(!row);
    }
    function save(e: FormEvent) {
        e.preventDefault();
        form.transform((data) => ({ ...data, ...(edit ? { _method: 'put' } : {}) }));
        form.post(edit ? `${path}/${edit.id}` : path, {
            forceFormData: true,
            preserveScroll: true,
            onSuccess: () => {
                setEdit(null);
                setCreating(false);
            },
        });
    }
    function openAction(row: Row, action: string, label: string) {
        actionForm.clearErrors();
        actionForm.setData(
            action === 'receive' && d.key === 'purchases'
                ? {
                      idempotency_key: crypto.randomUUID(),
                      quantities: Object.fromEntries(
                          row.items.map((item: Row) => [
                              item.id,
                              Math.max(0, Number(item.quantity) - Number(item.received)),
                          ]),
                      ),
                  }
                : action === 'adjust' || action === 'count'
                  ? { quantity: action === 'count' ? row.quantity : '', reason: '' }
                  : {},
        );
        setWorkflow({ row, action, label });
    }
    function runAction(e: FormEvent) {
        e.preventDefault();
        if (!workflow) {
            return;
        }
        actionForm.post(`${path}/${workflow.row.id}/${workflow.action}`, {
            preserveScroll: true,
            onSuccess: () => setWorkflow(null),
        });
    }
    function actions(row: Row) {
        const result: React.ReactNode[] = [];
        const button = (action: string, label: string, Icon = Check) =>
            result.push(
                <Button
                    key={action}
                    variant="outline"
                    size="sm"
                    onClick={() => openAction(row, action, label)}
                >
                    <Icon size={14} />
                    {label}
                </Button>,
            );
        if (d.key === 'orders') {
            result.push(
                <Button
                    key="receipt"
                    variant="ghost"
                    size="sm"
                    asChild
                >
                    <Link href={`/orders/${row.id}`}>Lihat struk</Link>
                </Button>,
            );
        }
        if (['recipes', 'purchases', 'receipts', 'payroll', 'audit'].includes(d.key)) {
            result.push(
                <Button
                    key="detail"
                    variant="ghost"
                    size="sm"
                    onClick={() => setDetail(row)}
                >
                    <Eye size={14} />
                    Detail
                </Button>,
            );
        }
        if (
            canWrite &&
            !immutable.includes(d.key) &&
            !(d.key === 'users' && row.role === 'owner') &&
            !(d.key === 'expenses' && row.approval_status !== 'pending') &&
            !(d.key === 'payroll' && row.status !== 'draft')
        ) {
            result.push(
                <Button
                    key="edit"
                    variant="ghost"
                    size="sm"
                    onClick={() => openForm(row)}
                    aria-label={`Ubah ${row.name ?? row.id}`}
                >
                    <Pencil size={14} />
                    Ubah
                </Button>,
            );
        }
        if (d.key === 'branch-prices' && user.role !== 'cashier') {
            button(
                'availability',
                row.is_available ? 'Nonaktifkan menu' : 'Aktifkan menu',
            );
        }
        if (d.key === 'inventory') {
            button('adjust', 'Penyesuaian', SlidersHorizontal);
            button('count', 'Opname', ClipboardCheck);
        }
        if (d.key === 'purchases' && ['ordered', 'partial'].includes(row.status)) {
            button('receive', 'Terima bahan', PackageCheck);
            if (row.status === 'ordered') {
                button('cancel', 'Batalkan', X);
            }
        }
        if (d.key === 'transfers' && row.status === 'pending' && user.role === 'owner') {
            button('ship', 'Setujui & kirim');
            button('reject', 'Tolak transfer', X);
        }
        if (
            d.key === 'transfers' &&
            row.status === 'shipped' &&
            (user.role === 'owner' || user.branch_id === row.destination_branch_id)
        ) {
            button('receive', 'Terima stok');
        }
        if (
            ['expenses', 'price-requests', 'refunds'].includes(d.key) &&
            (row.approval_status ?? row.status) === 'pending' &&
            (user.role === 'owner' || (d.key === 'refunds' && user.role === 'manager'))
        ) {
            button('approve', 'Setujui');
            button('reject', 'Tolak', X);
        }
        if (d.key === 'payroll' && row.status === 'draft') {
            button('approve', 'Setujui payroll');
        }
        if (d.key === 'payroll' && row.status === 'approved') {
            button('pay', 'Catat dibayar');
        }
        if (d.key === 'expenses' && row.receipt_path) {
            result.push(
                <a
                    key="proof"
                    href={`/expenses/${row.id}/receipt`}
                    target="_blank"
                    rel="noreferrer"
                    className="text-link"
                >
                    Bukti
                </a>,
            );
        }
        return result;
    }
    const numeric = (key: string) =>
        [
            'price',
            'amount',
            'base_price',
            'stock_value',
            'estimated_cost',
            'net_pay',
            'total_amount',
            'quantity',
            'balance',
            'minimum',
            'unit_cost',
            'opening_cash',
            'expected_cash',
            'actual_cash',
            'version',
            'items_count',
            'users_count',
            'entity_id',
        ].includes(key);
    const special =
        d.key === 'inventory' ? (
            <div className="context-strip">
                <ClipboardCheck size={20} />
                <div>
                    <strong>Stok opname tanpa kehilangan riwayat</strong>
                    <p>
                        Masukkan jumlah fisik melalui Opname. Selisihnya akan dicatat
                        dengan alasan dan penanggung jawab.
                    </p>
                </div>
                <Link href="/manage/movements">Riwayat stok</Link>
            </div>
        ) : d.key === 'payroll' ? (
            <div className="context-strip">
                <CircleCheck size={20} />
                <div>
                    <strong>Gaji pokok + tunjangan + bonus − potongan</strong>
                    <p>
                        Persetujuan mengunci nilai payroll. Pembayaran dicatat manual
                        setelah transfer dilakukan.
                    </p>
                </div>
            </div>
        ) : null;
    return (
        <>
            <Head title={d.title} />
            <PageTitle
                title={d.title}
                description={d.description}
                action={
                    canWrite && d.key !== 'branch-prices' ? (
                        <Button onClick={() => openForm()}>
                            <Plus size={16} />
                            {d.key === 'recipes'
                                ? 'Buat versi resep'
                                : `Tambah ${d.key === 'branches' ? 'cabang' : d.key === 'users' ? 'pengguna' : d.key === 'products' ? 'produk' : 'data'}`}
                        </Button>
                    ) : undefined
                }
            />
            <div className="section-tabs">
                <span className="tab-active">{d.title}</span>
                <span className="muted">{number(records.total)} catatan</span>
            </div>
            {special}
            <form
                className="list-toolbar"
                onSubmit={(e) => {
                    e.preventDefault();
                    router.get(
                        path,
                        {
                            q,
                            sort,
                            direction: sort === 'id' ? 'desc' : 'asc',
                            ...(categoryFilter ? { category_id: categoryFilter } : {}),
                            ...(branchFilter ? { branch_id: branchFilter } : {}),
                            ...(start ? { start } : {}),
                            ...(end ? { end } : {}),
                            ...(expenseCategory ? { category: expenseCategory } : {}),
                        },
                        { preserveState: true, preserveScroll: true },
                    );
                }}
            >
                <SearchBox
                    value={q}
                    onChange={setQ}
                    placeholder={`Cari ${d.title.toLowerCase()}…`}
                />
                {[
                    'orders',
                    'shifts',
                    'expenses',
                    'payroll',
                    'schedules',
                    'purchases',
                    'receipts',
                    'movements',
                    'refunds',
                    'audit',
                ].includes(d.key) && (
                    <>
                        <input
                            type="date"
                            aria-label="Filter tanggal mulai"
                            value={start}
                            onChange={(e) => setStart(e.target.value)}
                        />
                        <input
                            type="date"
                            aria-label="Filter tanggal selesai"
                            value={end}
                            min={start || undefined}
                            onChange={(e) => setEnd(e.target.value)}
                        />
                    </>
                )}
                {d.key === 'expenses' && (
                    <input
                        aria-label="Filter kategori pengeluaran"
                        placeholder="Kategori pengeluaran"
                        value={expenseCategory}
                        onChange={(e) => setExpenseCategory(e.target.value)}
                    />
                )}
                {d.key === 'products' && (
                    <select
                        aria-label="Filter kategori produk"
                        value={categoryFilter}
                        onChange={(e) => setCategoryFilter(e.target.value)}
                    >
                        <option value="">Semua kategori</option>
                        {options.categories?.map((category) => (
                            <option
                                key={category.value}
                                value={category.value}
                            >
                                {category.label}
                            </option>
                        ))}
                    </select>
                )}
                {user.role === 'owner' &&
                    [
                        'branches',
                        'branch-prices',
                        'inventory',
                        'purchases',
                        'receipts',
                        'employees',
                        'expenses',
                        'payroll',
                        'orders',
                        'shifts',
                        'movements',
                        'refunds',
                        'schedules',
                    ].includes(d.key) && (
                        <select
                            aria-label="Filter cabang data"
                            value={branchFilter}
                            onChange={(e) => setBranchFilter(e.target.value)}
                        >
                            <option value="">Seluruh cabang</option>
                            {options.branches?.map((branch) => (
                                <option
                                    key={branch.value}
                                    value={branch.value}
                                >
                                    {branch.label}
                                </option>
                            ))}
                        </select>
                    )}
                <select
                    aria-label="Urutkan data"
                    value={sort}
                    onChange={(e) => setSort(e.target.value)}
                >
                    <option value="id">Terbaru</option>
                    {Object.entries(d.columns)
                        .filter(
                            ([key]) =>
                                !key.includes('.') &&
                                ![
                                    'id',
                                    'users_count',
                                    'items_count',
                                    'stock_value',
                                    'estimated_cost',
                                ].includes(key),
                        )
                        .map(([key, label]) => (
                            <option
                                key={key}
                                value={key}
                            >
                                {String(label)} (naik)
                            </option>
                        ))}
                </select>
                <Button
                    variant="outline"
                    type="submit"
                    size="sm"
                >
                    Terapkan
                </Button>
                <span className="list-scope">
                    {user.branch?.city ?? 'Seluruh cabang'}
                </span>
            </form>
            <div className="table-scroll">
                <table>
                    <thead>
                        <tr>
                            {Object.entries(d.columns).map(([key, label]) => (
                                <th
                                    key={key}
                                    className={numeric(key) ? 'num' : ''}
                                >
                                    {String(label)}
                                </th>
                            ))}
                            <th className="actions-cell">
                                <span className="sr-only">Tindakan</span>
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        {records.data.map((row: Row) => (
                            <tr key={row.id}>
                                {Object.keys(d.columns).map((key, i) => (
                                    <td
                                        key={key}
                                        className={`${numeric(key) ? 'num' : ''} ${i === 0 ? 'identity-cell' : ''}`}
                                    >
                                        <Cell
                                            row={row}
                                            path={key}
                                        />
                                    </td>
                                ))}
                                <td className="actions-cell">
                                    <div className="row-actions">{actions(row)}</div>
                                </td>
                            </tr>
                        ))}
                    </tbody>
                </table>
                {!records.data.length && (
                    <Empty>
                        Belum ada {d.title.toLowerCase()}
                        {q ? ' yang sesuai pencarian.' : '.'}
                        {canWrite && (
                            <button
                                className="text-link"
                                onClick={() => openForm()}
                            >
                                {' '}
                                Tambah catatan pertama
                            </button>
                        )}
                    </Empty>
                )}
            </div>
            <Pagination records={records} />
            <Dialog
                open={creating || !!edit}
                onOpenChange={(open) => {
                    if (!open && !form.processing) {
                        setCreating(false);
                        setEdit(null);
                    }
                }}
                title={`${edit ? 'Ubah' : 'Tambah'} ${d.title.toLowerCase()}`}
                description={d.description}
            >
                <form onSubmit={save}>
                    <Errors errors={form.errors} />
                    <div className="form-grid">
                        {(d.fields as Field[]).map((f) => (
                            <div
                                key={f.key}
                                className={
                                    ['textarea', 'recipe', 'purchase'].includes(f.type)
                                        ? 'span-two'
                                        : ''
                                }
                            >
                                {f.type === 'checkbox' ? (
                                    <label className="checkbox-label form-checkbox">
                                        <input
                                            type="checkbox"
                                            checked={!!form.data[f.key]}
                                            onChange={(e) =>
                                                form.setData(f.key, e.target.checked)
                                            }
                                        />
                                        {f.label}
                                    </label>
                                ) : ['recipe', 'purchase'].includes(f.type) ? (
                                    <fieldset>
                                        <legend>{f.label}</legend>
                                        <div className="repeat-fields">
                                            {(form.data[f.key] ?? []).map(
                                                (item: Row, index: number) => (
                                                    <div
                                                        className="repeat-row"
                                                        key={index}
                                                    >
                                                        <select
                                                            aria-label={`Bahan ${index + 1}`}
                                                            value={item.ingredient_id}
                                                            required
                                                            onChange={(e) => {
                                                                const list = [
                                                                    ...form.data[f.key],
                                                                ];
                                                                list[index] = {
                                                                    ...item,
                                                                    ingredient_id:
                                                                        e.target.value,
                                                                };
                                                                form.setData(f.key, list);
                                                            }}
                                                        >
                                                            <option value="">
                                                                Pilih bahan
                                                            </option>
                                                            {options.ingredients?.map(
                                                                (o) => (
                                                                    <option
                                                                        key={o.value}
                                                                        value={o.value}
                                                                    >
                                                                        {o.label}
                                                                    </option>
                                                                ),
                                                            )}
                                                        </select>
                                                        <input
                                                            type="number"
                                                            min="0.001"
                                                            step="0.001"
                                                            aria-label={`Jumlah bahan ${index + 1}`}
                                                            placeholder="Jumlah"
                                                            required
                                                            value={item.quantity}
                                                            onChange={(e) => {
                                                                const list = [
                                                                    ...form.data[f.key],
                                                                ];
                                                                list[index] = {
                                                                    ...item,
                                                                    quantity:
                                                                        e.target.value,
                                                                };
                                                                form.setData(f.key, list);
                                                            }}
                                                        />
                                                        {f.type === 'purchase' && (
                                                            <input
                                                                type="number"
                                                                min="0"
                                                                step="1"
                                                                aria-label={`Harga per satuan ${index + 1}`}
                                                                placeholder="Rp / satuan"
                                                                required
                                                                value={item.unit_price}
                                                                onChange={(e) => {
                                                                    const list = [
                                                                        ...form.data[
                                                                            f.key
                                                                        ],
                                                                    ];
                                                                    list[index] = {
                                                                        ...item,
                                                                        unit_price:
                                                                            e.target
                                                                                .value,
                                                                    };
                                                                    form.setData(
                                                                        f.key,
                                                                        list,
                                                                    );
                                                                }}
                                                            />
                                                        )}
                                                        <Button
                                                            variant="ghost"
                                                            size="icon"
                                                            type="button"
                                                            aria-label={`Hapus bahan ${index + 1}`}
                                                            onClick={() =>
                                                                form.setData(
                                                                    f.key,
                                                                    form.data[
                                                                        f.key
                                                                    ].filter(
                                                                        (
                                                                            _: Row,
                                                                            i: number,
                                                                        ) => i !== index,
                                                                    ),
                                                                )
                                                            }
                                                        >
                                                            <X size={16} />
                                                        </Button>
                                                    </div>
                                                ),
                                            )}
                                        </div>
                                        <Button
                                            variant="outline"
                                            size="sm"
                                            type="button"
                                            onClick={() =>
                                                form.setData(f.key, [
                                                    ...form.data[f.key],
                                                    {
                                                        ingredient_id: '',
                                                        quantity: '',
                                                        ...(f.type === 'purchase'
                                                            ? { unit_price: '' }
                                                            : {}),
                                                    },
                                                ])
                                            }
                                        >
                                            <Plus size={14} />
                                            Tambah bahan
                                        </Button>
                                    </fieldset>
                                ) : (
                                    <label className="field">
                                        {f.label}
                                        {f.type === 'select' ? (
                                            <select
                                                value={form.data[f.key] ?? ''}
                                                required={f.rules.includes('required')}
                                                onChange={(e) =>
                                                    form.setData(f.key, e.target.value)
                                                }
                                            >
                                                <option value="">
                                                    Pilih {f.label.toLowerCase()}
                                                </option>
                                                {options[f.source ?? '']?.map((o) => (
                                                    <option
                                                        key={o.value}
                                                        value={o.value}
                                                    >
                                                        {o.label}
                                                    </option>
                                                ))}
                                            </select>
                                        ) : f.type === 'textarea' ? (
                                            <textarea
                                                rows={3}
                                                value={form.data[f.key] ?? ''}
                                                onChange={(e) =>
                                                    form.setData(f.key, e.target.value)
                                                }
                                            />
                                        ) : f.type === 'file' ? (
                                            <input
                                                type="file"
                                                accept="image/jpeg,image/png,application/pdf"
                                                onChange={(e) =>
                                                    form.setData(
                                                        f.key,
                                                        e.target.files?.[0] ?? null,
                                                    )
                                                }
                                            />
                                        ) : (
                                            <input
                                                type={f.type}
                                                step={
                                                    f.type === 'number'
                                                        ? 'any'
                                                        : undefined
                                                }
                                                value={form.data[f.key] ?? ''}
                                                required={
                                                    f.rules.includes('required') &&
                                                    !(edit && f.type === 'password')
                                                }
                                                autoComplete={
                                                    f.type === 'password'
                                                        ? 'new-password'
                                                        : undefined
                                                }
                                                onChange={(e) =>
                                                    form.setData(f.key, e.target.value)
                                                }
                                            />
                                        )}
                                    </label>
                                )}
                            </div>
                        ))}
                    </div>
                    <div className="dialog-footer">
                        <Button
                            variant="outline"
                            type="button"
                            onClick={() => {
                                setCreating(false);
                                setEdit(null);
                            }}
                            disabled={form.processing}
                        >
                            Batal
                        </Button>
                        <Button disabled={form.processing}>
                            {form.processing ? 'Menyimpan…' : 'Simpan data'}
                        </Button>
                    </div>
                </form>
            </Dialog>
            <Dialog
                open={!!workflow}
                onOpenChange={(open) => {
                    if (!open && !actionForm.processing) {
                        setWorkflow(null);
                    }
                }}
                title={workflow?.label ?? ''}
                description="Periksa rincian sebelum mengonfirmasi. Aktivitas ini akan dicatat dalam audit log."
            >
                <form onSubmit={runAction}>
                    <Errors errors={actionForm.errors} />
                    {workflow && (
                        <>
                            <p className="workflow-record">
                                {workflow.row.name ??
                                    workflow.row.number ??
                                    workflow.row.order?.order_number ??
                                    workflow.row.ingredient?.name ??
                                    workflow.row.employee?.name ??
                                    `Catatan #${workflow.row.id}`}
                            </p>
                            {['adjust', 'count'].includes(workflow.action) && (
                                <>
                                    <label className="field">
                                        {workflow.action === 'count'
                                            ? 'Jumlah fisik yang dihitung'
                                            : 'Perubahan stok (+ / −)'}
                                        <input
                                            type="number"
                                            step="0.001"
                                            min={
                                                workflow.action === 'count'
                                                    ? 0
                                                    : undefined
                                            }
                                            required
                                            value={actionForm.data.quantity}
                                            onChange={(e) =>
                                                actionForm.setData(
                                                    'quantity',
                                                    e.target.value,
                                                )
                                            }
                                        />
                                    </label>
                                    <label className="field">
                                        Alasan
                                        <textarea
                                            required
                                            minLength={5}
                                            rows={3}
                                            value={actionForm.data.reason}
                                            onChange={(e) =>
                                                actionForm.setData(
                                                    'reason',
                                                    e.target.value,
                                                )
                                            }
                                        />
                                    </label>
                                </>
                            )}
                            {d.key === 'purchases' &&
                                workflow.action === 'receive' &&
                                workflow.row.items.map((item: Row) => (
                                    <label
                                        key={item.id}
                                        className="field"
                                    >
                                        {item.ingredient.name} · sisa{' '}
                                        {number(
                                            Number(item.quantity) - Number(item.received),
                                        )}{' '}
                                        {item.ingredient.unit}
                                        <input
                                            type="number"
                                            min="0"
                                            step="0.001"
                                            max={
                                                Number(item.quantity) -
                                                Number(item.received)
                                            }
                                            value={
                                                actionForm.data.quantities?.[item.id] ?? 0
                                            }
                                            onChange={(e) =>
                                                actionForm.setData('quantities', {
                                                    ...actionForm.data.quantities,
                                                    [item.id]: e.target.value,
                                                })
                                            }
                                        />
                                    </label>
                                ))}
                        </>
                    )}
                    <div className="dialog-footer">
                        <Button
                            variant="outline"
                            type="button"
                            onClick={() => setWorkflow(null)}
                            disabled={actionForm.processing}
                        >
                            Batal
                        </Button>
                        <Button disabled={actionForm.processing}>
                            {actionForm.processing ? 'Memproses…' : 'Konfirmasi'}
                        </Button>
                    </div>
                </form>
            </Dialog>
            <Dialog
                open={!!detail}
                onOpenChange={(open) => {
                    if (!open) {
                        setDetail(null);
                    }
                }}
                title={d.key === 'payroll' ? 'Slip gaji' : 'Rincian catatan'}
            >
                {detail && (
                    <div className="record-detail">
                        {d.key === 'audit' ? (
                            <pre className="audit-detail">
                                {JSON.stringify(detail.changes, null, 2)}
                            </pre>
                        ) : d.key === 'payroll' ? (
                            <>
                                <div className="slip-header">
                                    <strong>VELTRIX</strong>
                                    <span>{detail.employee?.name}</span>
                                    <span>{detail.branch?.name}</span>
                                    <span>Periode {detail.period}</span>
                                </div>
                                {[
                                    ['Gaji pokok', 'base_salary'],
                                    ['Tunjangan', 'allowance'],
                                    ['Bonus', 'bonus'],
                                    ['Potongan', 'deduction'],
                                    ['Gaji bersih', 'net_pay'],
                                ].map(([label, key]) => (
                                    <div
                                        className="key-value"
                                        key={key}
                                    >
                                        <span>{label}</span>
                                        <strong>{money(detail[key])}</strong>
                                    </div>
                                ))}
                                <Status value={detail.status} />
                                <Button
                                    variant="outline"
                                    onClick={() => window.print()}
                                >
                                    <Printer size={16} />
                                    Cetak slip
                                </Button>
                            </>
                        ) : (
                            <>
                                {(detail.items ?? []).map((item: Row) => (
                                    <div
                                        className="key-value"
                                        key={item.id}
                                    >
                                        <div>
                                            <strong>{item.ingredient?.name}</strong>
                                            <p className="muted">
                                                {number(item.quantity)}{' '}
                                                {item.ingredient?.unit}
                                                {item.unit_price !== undefined
                                                    ? ` × ${money(item.unit_price)}`
                                                    : ''}
                                            </p>
                                        </div>
                                        {item.received !== undefined && (
                                            <span>Diterima {number(item.received)}</span>
                                        )}
                                    </div>
                                ))}
                            </>
                        )}
                    </div>
                )}
            </Dialog>
        </>
    );
}

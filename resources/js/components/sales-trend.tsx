import {
    ResponsiveContainer,
    LineChart,
    Line,
    XAxis,
    YAxis,
    CartesianGrid,
    Tooltip,
} from 'recharts';
import { date, money, type Row } from '../lib/utils';

export function SalesTrend({ report }: { report: Row }) {
    const m = report.metrics;
    return (
        <section className="panel revenue-panel">
            <div className="panel-header">
                <div>
                    <h2>Tren penjualan</h2>
                    <p>
                        Penjualan bersih per{' '}
                        {report.granularity === 'hour' ? 'jam' : 'hari'}, dalam Rupiah
                    </p>
                </div>
                <span className="series-key">Periode terpilih</span>
            </div>
            <div className="revenue-chart">
                <ResponsiveContainer
                    width="100%"
                    height="100%"
                >
                    <LineChart
                        data={report.trend}
                        margin={{ top: 12, right: 12, bottom: 8, left: 0 }}
                    >
                        <CartesianGrid
                            vertical={false}
                            stroke="#edf0ee"
                        />
                        <XAxis
                            dataKey="date"
                            axisLine={false}
                            tickLine={false}
                            minTickGap={40}
                            tick={{ fill: '#626b65', fontSize: 12 }}
                            tickFormatter={(v) =>
                                report.granularity === 'hour'
                                    ? new Date(v).toLocaleTimeString('id-ID', {
                                          hour: '2-digit',
                                          minute: '2-digit',
                                          timeZone: 'Asia/Jakarta',
                                      })
                                    : new Date(v + 'T12:00:00').toLocaleDateString(
                                          'id-ID',
                                          {
                                              day: 'numeric',
                                              month: 'short',
                                          },
                                      )
                            }
                        />
                        <YAxis
                            axisLine={false}
                            tickLine={false}
                            width={58}
                            tick={{ fill: '#626b65', fontSize: 12 }}
                            tickFormatter={(v) =>
                                Math.abs(v) >= 1000000
                                    ? `${v / 1000000} jt`
                                    : Math.abs(v) >= 1000
                                      ? `${v / 1000} rb`
                                      : v
                            }
                            domain={m.transactions ? ['auto', 'auto'] : [0, 100000]}
                        />
                        <Tooltip
                            formatter={(v) => money(Number(v))}
                            labelFormatter={(v) => date(String(v))}
                            contentStyle={{
                                border: '1px solid #e4e9e6',
                                borderRadius: 8,
                            }}
                        />
                        <Line
                            dataKey="amount"
                            name="Penjualan bersih"
                            type="linear"
                            stroke="#166348"
                            strokeWidth={2}
                            dot={false}
                            activeDot={{ r: 4 }}
                            isAnimationActive={false}
                        />
                    </LineChart>
                </ResponsiveContainer>
            </div>
            {!m.transactions && (
                <p className="chart-caption">
                    Belum ada transaksi pada periode ini. Nilai ditampilkan sebagai nol.
                </p>
            )}
        </section>
    );
}

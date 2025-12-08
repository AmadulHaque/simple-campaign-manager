// resources/js/Pages/Dashboard.tsx
import { Head } from '@inertiajs/react';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Badge } from '@/components/ui/badge';
import { Progress } from '@/components/ui/progress';
import { ChartConfig, ChartContainer, ChartTooltip, ChartTooltipContent } from '@/components/ui/chart';
import { PieChart, Pie, Cell, ResponsiveContainer } from 'recharts';
import { Users, Send, TrendingUp } from 'lucide-react';
import AppLayout from '@/layouts/app-layout';

interface DashboardProps {
    stats: {
        contacts: number;
        campaigns: number;
        emailsSent: number;
        deliveryRate: string;
    };
    recentCampaigns: Array<{
        id: number;
        subject: string;
        created_at: string;
        sent_count: number;
        recipients: { total: number }[];
    }>;
    chartData: {
        sent: number;
        pending: number;
        failed: number;
    };
}

const chartConfig: ChartConfig = {
    sent: { label: "Sent", color: "#22c55e" },
    pending: { label: "Pending", color: "#f59e0b" },
    failed: { label: "Failed", color: "#ef4444" },
};

export default function Dashboard({ stats, recentCampaigns, chartData }: DashboardProps) {
    const pieData = [
        { name: 'Sent', value: chartData.sent, fill: '#22c55e' },
        { name: 'Pending', value: chartData.pending, fill: '#f59e0b' },
        { name: 'Failed', value: chartData.failed, fill: '#ef4444' },
    ].filter(item => item.value > 0);

    const StatCard = ({ title, value, icon: Icon, color = "text-blue-600" }) => (
        <Card>
            <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                <CardTitle className="text-sm font-medium">{title}</CardTitle>
                <Icon className={`h-4 w-4 ${color}`} />
            </CardHeader>
            <CardContent>
                <div className="text-2xl font-bold">{value}</div>
            </CardContent>
        </Card>
    );

    return (
        <>
            <AppLayout
                breadcrumbs={[
                    { title: 'Dashboard', href: '/dashboard' },
                ]}
            >
                <Head title="Dashboard" />
                <div className="flex h-full flex-col gap-6 overflow-hidden rounded-xl  p-6 shadow-sm">

                    <div className="space-y-6">
                        <div>
                            <h1 className="text-3xl font-bold tracking-tight">Dashboard</h1>
                            <p className="text-muted-foreground">Welcome back! Here's your email campaign overview.</p>
                        </div>

                        {/* Stats Grid */}
                        <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
                            <StatCard title="Total Contacts" value={stats.contacts.toLocaleString()} icon={Users} color="text-indigo-600" />
                            <StatCard title="Total Campaigns" value={stats.campaigns} icon={TrendingUp} color="text-purple-600" />
                            <StatCard title="Emails Sent" value={stats.emailsSent.toLocaleString()} icon={Send} color="text-green-600" />
                            <StatCard
                                title="Delivery Rate"
                                value={stats.deliveryRate}
                                icon={TrendingUp}
                                color="text-emerald-600"
                            />
                        </div>

                        <div className="grid gap-6 md:grid-cols-2 lg:grid-cols-7">
                            {/* Delivery Status Chart */}
                            <Card className="col-span-1 lg:col-span-3">
                                <CardHeader>
                                    <CardTitle>Delivery Status</CardTitle>
                                    <CardDescription>Overall email delivery breakdown</CardDescription>
                                </CardHeader>
                                <CardContent className="pl-2">
                                    {pieData.length > 0 ? (
                                        <ChartContainer config={chartConfig} className="h-64">
                                            <ResponsiveContainer width="100%" height="100%">
                                                <PieChart>
                                                    <Pie
                                                        data={pieData}
                                                        cx="50%"
                                                        cy="50%"
                                                        innerRadius={60}
                                                        outerRadius={90}
                                                        paddingAngle={5}
                                                        dataKey="value"
                                                    >
                                                        {pieData.map((entry, index) => (
                                                            <Cell key={`cell-${index}`} fill={entry.fill} />
                                                        ))}
                                                    </Pie>
                                                    <ChartTooltip content={<ChartTooltipContent />} />
                                                </PieChart>
                                            </ResponsiveContainer>
                                        </ChartContainer>
                                    ) : (
                                        <div className="flex h-64 items-center justify-center text-muted-foreground">
                                            No delivery data yet
                                        </div>
                                    )}
                                </CardContent>
                            </Card>

                            {/* Recent Campaigns */}
                            <Card className="col-span-1 lg:col-span-4">
                                <CardHeader>
                                    <CardTitle>Recent Campaigns</CardTitle>
                                    <CardDescription>Your latest email campaigns</CardDescription>
                                </CardHeader>
                                <CardContent>
                                    <Table>
                                        <TableHeader>
                                            <TableRow>
                                                <TableHead>Subject</TableHead>
                                                <TableHead>Sent</TableHead>
                                                <TableHead>Date</TableHead>
                                            </TableRow>
                                        </TableHeader>
                                        <TableBody>
                                            {recentCampaigns.length > 0 ? (
                                                recentCampaigns.map((campaign) => (
                                                    <TableRow key={campaign.id}>
                                                        <TableCell className="font-medium">{campaign.subject}</TableCell>
                                                        <TableCell>
                                                            <Badge variant="outline" className="text-green-700">
                                                                {campaign.sent_count} sent
                                                            </Badge>
                                                        </TableCell>
                                                        <TableCell className="text-muted-foreground">
                                                            {new Date(campaign.created_at).toLocaleDateString()}
                                                        </TableCell>
                                                    </TableRow>
                                                ))
                                            ) : (
                                                <TableRow>
                                                    <TableCell colSpan={3} className="text-center text-muted-foreground">
                                                        No campaigns yet
                                                    </TableCell>
                                                </TableRow>
                                            )}
                                        </TableBody>
                                    </Table>
                                </CardContent>
                            </Card>
                        </div>
                    </div>
                </div>
            </AppLayout>
        </>
    );
}
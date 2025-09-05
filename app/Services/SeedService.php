<?php
namespace App\Services;
use App\Models\Product;
use App\Models\Outlet;
use Carbon\Carbon;


class SeedService
{
    public static function isiProduct() {
        $now= Carbon::now();
        $product = [
            ['nama' => 'CAKE MINI', 'kode' => 'CMNI'],
            ['nama' => 'CAKE MINI CP', 'kode' => 'CMCP'],
            ['nama' => 'CAKE TOPPER', 'kode' => 'CTPR'],
            ['nama' => 'CAKE BESAR', 'kode' => 'CBSR'],
            ['nama' => 'CAKE KUCING', 'kode' => 'CKCG'],
            ['nama' => 'CAKE LABUBU', 'kode' => 'CLBB'],
            ['nama' => 'CAKE CHOCO CREAM', 'kode' => 'CCCR'],
            ['nama' => 'CAKE STRAWBERRY', 'kode' => 'CSTR'],
            ['nama' => 'CAKE BLUEBERRY', 'kode' => 'CBLB'],
            ['nama' => 'CAKE MIXBERRIES', 'kode' => 'CMBR'],
            ['nama' => 'CAKE TIRAMISU', 'kode' => 'CTRM'],
            ['nama' => 'CAKE CARAMEL', 'kode' => 'CCRM'],
            ['nama' => 'CAKE KOREAN KARAKT', 'kode' => 'CKKR'],
            ['nama' => 'CAKE KOREAN COKLAT', 'kode' => 'CKKC'],
            ['nama' => 'CAKE KOREAN ROYALE', 'kode' => 'CKRY'],
            ['nama' => 'CAKE COKLAT KEJU', 'kode' => 'CCKJ'],
            ['nama' => 'CAKE KACANG', 'kode' => 'CKGN'],
            ['nama' => 'CAKE OREO', 'kode' => 'CORO'],
            ['nama' => 'CAKE BLACKFOREST', 'kode' => 'CBFR'],
            ['nama' => 'SWISSROLL', 'kode' => 'SWRL'],
            ['nama' => 'LC COKLAT', 'kode' => 'LCKT'],
            ['nama' => 'LC REDVELVET', 'kode' => 'RVVT'],
            ['nama' => 'LC OREO', 'kode' => 'LORO'],
            ['nama' => 'LC TIRAMISU', 'kode' => 'LTRM'],
            ['nama' => 'SWISSROLL KACANG', 'kode' => 'SKCG'],
            ['nama' => 'SWISSROLL SLICE', 'kode' => 'SSLC'],
            ['nama' => 'LC DEKOR', 'kode' => 'LDKR'],
            ['nama' => 'LC COKLAT CP', 'kode' => 'LCCP'],
            ['nama' => 'LC REDVELVET CP', 'kode' => 'LRVV'],
            ['nama' => 'MOUSSE COKLAT', 'kode' => 'MSCK'],
            ['nama' => 'MOUSSE STRAWBERRY', 'kode' => 'MSTW'],
            ['nama' => 'MOUSSE REDVELVET', 'kode' => 'MSRV'],
            ['nama' => 'MOUSSE KOREAN KARAKT', 'kode' => 'MKKR'],
            ['nama' => 'MOUSSE TIRAMISU', 'kode' => 'MSTR'],
            ['nama' => 'MOUSSE KOREAN COKLAT', 'kode' => 'MKKC'],
            ['nama' => 'MOUSSE KOREAN ROYALE', 'kode' => 'MKRY'],
            ['nama' => 'MOUSSE COKLAT CP', 'kode' => 'MSCC'],
            ['nama' => 'MOUSSE STRAWBERRY CP', 'kode' => 'MSTC'],
            ['nama' => 'MOUSSE REDVELVET CP', 'kode' => 'MSRC'],
            ['nama' => 'MOUSSE KOREAN KARAKT CP', 'kode' => 'MKRC'],
            ['nama' => 'MOUSSE TIRAMISU CP', 'kode' => 'MTRC'],
            ['nama' => 'BROWNIES COKLAT', 'kode' => 'BRCK'],
            ['nama' => 'BROWNIES MINI', 'kode' => 'BMNI'],
            ['nama' => 'BROWNIES COKLAT KEJU', 'kode' => 'BCKJ'],
            ['nama' => 'BROWNIES KACANG', 'kode' => 'BKGN'],
            ['nama' => 'BROWNIES OREO', 'kode' => 'BORO'],
            ['nama' => 'BROWNIES MIXBERRIES', 'kode' => 'BMBR'],
            ['nama' => 'BROWNIES TIRAMISU', 'kode' => 'BTRM'],
            ['nama' => 'BROWNIES CARAMEL', 'kode' => 'BCRM'],
            ['nama' => 'BROWNIES CHOCO CREAM', 'kode' => 'BCHC'],
            ['nama' => 'BROWNIES BLACKFOREST', 'kode' => 'BBFR'],
            ['nama' => 'BROWNIES KEJU', 'kode' => 'BRKJ'],
            ['nama' => 'BROWNIES KACANG', 'kode' => 'BRKG'],
            ['nama' => 'BROWNIES OREO', 'kode' => 'BROR'],
            ['nama' => 'BROWNIES MIXBERRIES', 'kode' => 'BRMB'],
            ['nama' => 'BROWNIES TIRAMISU', 'kode' => 'BRTM'],
            ['nama' => 'BROWNIES CARAMEL', 'kode' => 'BRCR'],
            ['nama' => 'BROWNIES CHOCO CREAM', 'kode' => 'BRCC'],
            ['nama' => 'BROWNIES BLACKFOREST', 'kode' => 'BRBF'],
            ['nama' => 'DONAT MINI', 'kode' => 'DMNI'],
            ['nama' => 'DONAT JUMBO', 'kode' => 'DJMB'],
            ['nama' => 'DONAT ISI COKLAT', 'kode' => 'DICK'],
            ['nama' => 'DONAT ISI KACANG', 'kode' => 'DIKG'],
            ['nama' => 'DONAT ISI KEJU', 'kode' => 'DIKJ'],
            ['nama' => 'DONAT ISI OREO', 'kode' => 'DIRO'],
            ['nama' => 'DONAT ISI CREAM', 'kode' => 'DICM'],
            ['nama' => 'DONAT ISI NUTELLA', 'kode' => 'DINT'],
            ['nama' => 'DONAT ISI STRAWBERRY', 'kode' => 'DIST'],
            ['nama' => 'DONAT ISI BLUEBERRY', 'kode' => 'DIBL'],
            ['nama' => 'DONAT ISI MIXBERRIES', 'kode' => 'DIMB'],
            ['nama' => 'DONAT ISI TIRAMISU', 'kode' => 'DITM'],
            ['nama' => 'DONAT ISI CARAMEL', 'kode' => 'DICR'],
            ['nama' => 'DONAT ISI CHOCO CREAM', 'kode' => 'DICC'],
            ['nama' => 'DONAT ISI BLACKFOREST', 'kode' => 'DIBF'],
            ['nama' => 'PASTRY APPLE PIE', 'kode' => 'PAPL'],
            ['nama' => 'PASTRY BOLA COKLAT', 'kode' => 'PBCO'],
            ['nama' => 'PASTRY BOLA KEJU', 'kode' => 'PBKJ'],
            ['nama' => 'PASTRY BOLA KACANG', 'kode' => 'PBKG'],
            ['nama' => 'PASTRY BOLA OREO', 'kode' => 'PBOR'],
            ['nama' => 'PASTRY BOLA CREAM', 'kode' => 'PBCM'],
            ['nama' => 'PASTRY BOLA NUTELLA', 'kode' => 'PBNT'],
            ['nama' => 'PASTRY BOLA STRAWBERRY', 'kode' => 'PBST'],
            ['nama' => 'PASTRY BOLA BLUEBERRY', 'kode' => 'PBBL'],
            ['nama' => 'PASTRY BOLA MIXBERRIES', 'kode' => 'PBMB'],
            ['nama' => 'PASTRY BOLA TIRAMISU', 'kode' => 'PBTM'],
            ['nama' => 'PASTRY BOLA CARAMEL', 'kode' => 'PBCR'],
            ['nama' => 'PASTRY BOLA CHOCO CREAM', 'kode' => 'PBCC'],
            ['nama' => 'PASTRY BOLA BLACKFOREST', 'kode' => 'PBBF'],
        ];
        $product = array_map(function ($r) use ($now) {
            return $r + [
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }, $product);

        Product::query()->insert($product);
        return Product::all();
    }

    public static function isiOutlet() {
        $now= Carbon::now();
        $outlet = [
            ['nama' => 'Outlet Pusat', 'kode' => 'OUTPUS', 'alamat' => 'Jl. Merdeka No.1', 'phone' => '021-123456'],
            ['nama' => 'Outlet Cabang 1', 'kode' => 'OUTCB1', 'alamat' => 'Jl. Sudirman No.2', 'phone' => '021-654321'],
            ['nama' => 'Outlet Cabang 2', 'kode' => 'OUTCB2', 'alamat' => 'Jl. Thamrin No.3', 'phone' => '021-112233'],
            ['nama' => 'Outlet Cabang 3', 'kode' => 'OUTCB3', 'alamat' => 'Jl. Gatot Subroto No.4', 'phone' => '021-445566'],
            ['nama' => 'Outlet Cabang 4', 'kode' => 'OUTCB4', 'alamat' => 'Jl. Rasuna Said No.5', 'phone' => '021-778899'],
            ['nama' => 'Outlet Cabang 5', 'kode' => 'OUTCB5', 'alamat' => 'Jl. Kuningan No.6', 'phone' => '021-998877'],
            ['nama' => 'Outlet Cabang 6', 'kode' => 'OUTCB6', 'alamat' => 'Jl. Cikini No.7', 'phone' => '021-556677'],
            ['nama' => 'Outlet Cabang 7', 'kode' => 'OUTCB7', 'alamat' => 'Jl. Kemang No.8', 'phone' => '021-334455'],
            ['nama' => 'Outlet Cabang 8', 'kode' => 'OUTCB8', 'alamat' => 'Jl. Blok M No.9', 'phone' => '021-223344'],
            ['nama' => 'Outlet Cabang 9', 'kode' => 'OUTCB9', 'alamat' => 'Jl. Puri Indah No.10', 'phone' => '021-667788'],
            ['nama' => 'Outlet Cabang 10', 'kode' => 'OUTCB10', 'alamat' => 'Jl. Pluit No.11', 'phone' => '021-445577'],
            ['nama' => 'Outlet Cabang 11', 'kode' => 'OUTCB11', 'alamat' => 'Jl. Ancol No.12', 'phone' => '021-889900'],
            ['nama' => 'Outlet Cabang 12', 'kode' => 'OUTCB12', 'alamat' => 'Jl. Senayan No.13', 'phone' => '021-334488'],
            ['nama' => 'Outlet Cabang 13', 'kode' => 'OUTCB13', 'alamat' => 'Jl. Kota No.14', 'phone' => '021-556644'],
            ['nama' => 'Outlet Cabang 14', 'kode' => 'OUTCB14', 'alamat' => 'Jl. Taman Mini No.15', 'phone' => '021-778866'],
            ['nama' => 'Outlet Cabang 15', 'kode' => 'OUTCB15', 'alamat' => 'Jl. Cilandak No.16', 'phone' => '021-990011'],
            ['nama' => 'Outlet Cabang 16', 'kode' => 'OUTCB16', 'alamat' => 'Jl. Tebet No.17', 'phone' => '021-223355'],
            ['nama' => 'Outlet Cabang 17', 'kode' => 'OUTCB17', 'alamat' => 'Jl. Matraman No.18', 'phone' => '021-667799'],
            ['nama' => 'Outlet Cabang 18', 'kode' => 'OUTCB18', 'alamat' => 'Jl. Pasar Minggu No.19', 'phone' => '021-445566'],
            ['nama' => 'Outlet Cabang 19', 'kode' => 'OUTCB19', 'alamat' => 'Jl. Kalimalang No.20', 'phone' => '021-889922'],
            ['nama' => 'Outlet Cabang 20', 'kode' => 'OUTCB20', 'alamat' => 'Jl. Bekasi No.21', 'phone' => '021-334477'],
            ['nama' => 'Outlet Cabang 21', 'kode' => 'OUTCB21', 'alamat' => 'Jl. Depok No.22', 'phone' => '021-556699'],
            ['nama' => 'Outlet Cabang 22', 'kode' => 'OUTCB22', 'alamat' => 'Jl. Bogor No.23', 'phone' => '021-778800'],
            ['nama' => 'Outlet Cabang 23', 'kode' => 'OUTCB23', 'alamat' => 'Jl. Tangerang No.24', 'phone' => '021-990022'],
            ['nama' => 'Outlet Cabang 24', 'kode' => 'OUTCB24', 'alamat' => 'Jl. Serpong No.25', 'phone' => '021-223366'],
            ['nama' => 'Outlet Cabang 25', 'kode' => 'OUTCB25', 'alamat' => 'Jl. BSD No.26', 'phone' => '021-667700'],
            ['nama' => 'Outlet Cabang 26', 'kode' => 'OUTCB26', 'alamat' => 'Jl. Cengkareng No.27', 'phone' => '021-445588'],
            ['nama' => 'Outlet Cabang 27', 'kode' => 'OUTCB27', 'alamat' => 'Jl. Daan Mogot No.28', 'phone' => '021-889933'],
            ['nama' => 'Outlet Cabang 28', 'kode' => 'OUTCB28', 'alamat' => 'Jl. Meruya No.29', 'phone' => '021-334499'],
            ['nama' => 'Outlet Cabang 29', 'kode' => 'OUTCB29', 'alamat' => 'Jl. Kebon Jeruk No.30', 'phone' => '021-556600'],
            ['nama' => 'Outlet Cabang 30', 'kode' => 'OUTCB30', 'alamat' => 'Jl. Palmerah No.31', 'phone' => '021-778811'],
        ];
        $outlet = array_map(function ($r) use ($now) {
            return $r + [
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }, $outlet);

        Outlet::query()->insert($outlet);
        return Outlet::all();
    }
}
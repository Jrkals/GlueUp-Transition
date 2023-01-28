<?php

namespace App\Console\Commands;

use App\Helpers\CSVReader;
use App\Helpers\ExcelWriter;
use App\Helpers\StringHelpers;
use Illuminate\Console\Command;

class ImportSeek extends Command {
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'import:seek {file}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Creates Xlsx file for glueup seek23 imports';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle() {
        $reader = new CSVReader( $this->argument( 'file' ) );

        $inputData = $reader->extract_data();
        foreach ( $inputData as $row ) {
            $chapaterInterest = '';
            if ( ! empty( $row['chapter_interest_list'] ) ) {
                $chapaterInterest = explode( ',', $row['chapter_interest_list'] )[0];
            }

            $data[] = [
                'First Name'            => $row['first_name'],
                'Last Name'             => $row['last_name'],
                'Email'                 => $row['email'],
                'Chapter Interest List' => StringHelpers::mapChapterInterestList( $chapaterInterest )
            ];
        }

        $writer = new ExcelWriter( './storage/app/exports/seek.xlsx' );
        $writer->writeSingleFileExcel( $data );


        return Command::SUCCESS;
    }
}

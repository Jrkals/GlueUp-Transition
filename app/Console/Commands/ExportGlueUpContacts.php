<?php

namespace App\Console\Commands;

use App\Helpers\ExcelWriter;
use App\Models\YcpContact;
use Illuminate\Console\Command;

class ExportGlueUpContacts extends Command {
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'glueup:exportContacts';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Export Contact Info to CSV';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle() {
        $this->line( 'exporing csv for Contacts ' );
        $writer   = new ExcelWriter( './storage/app/exports/contacts/contacts.xlsx' );
        $data     = [];
        $contacts = YcpContact::query()->with( [
            'plans',
            'chapters',
            'companies',
            'phones'
        ] )->get();
        $count    = 0;
        foreach ( $contacts as $contact ) {
            $address = $contact->address;
            $phone   = $contact->primaryPhone();

            $homeChapter            = $contact->homeChapter()?->glueupId() ?? '';
            $chapters               = $contact->chapterIds();
            $companyName            = $contact->companyName();
            $row                    = $contact->attributesToArray();
            $row['Street']          = $address->street1 ?? '';
            $row['Street 2']        = $address->street2 ?? '';
            $row['City']            = $address->city ?? '';
            $row['State']           = $address->state ?? '';
            $row['Postal Code']     = $address->postal_code ?? '';
            $row['Company']         = $companyName;
            $row['Primary Chapter'] = $homeChapter;
            $row['Chapters']        = $chapters;
            $row['Mobile Phone']    = $phone?->number;
            $row['Email']           = $contact->email;

            $data[] = $row;
            $count ++;
            if ( $count % 1000 === 0 ) {
                $this->line( $count . ' done ' . ( sizeof( $contacts ) - $count ) . ' remaining ' );
            }
        }
        $writer->writeSingleFileExcel( $data );

        return Command::SUCCESS;
    }
}

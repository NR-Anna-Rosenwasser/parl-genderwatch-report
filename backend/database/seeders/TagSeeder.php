<?php

namespace Database\Seeders;

use App\Models\Tag;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class TagSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('Clearing tags table...');
        Tag::truncate();

        $this->command->info("Querying Webservice for tags...");
        $tags = (new \App\Helpers\Webservice())->query(
            model: 'Tags',
            filter: "Language eq 'DE'",
            top: 1000
        );

        $this->command->info("Seeding tags...");
        foreach ($tags as $tag) {
            $this->command->info("Seeding tag {$tag['TagName']} ({$tag['ID']})");
            Tag::create([
                "externalID" => $tag['ID'],
                "name" => $tag['TagName'],
            ]);
        }
    }
}

<?php
namespace Bs\Console;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @author Michael Mifsud <info@tropotek.com>
 * @see http://www.tropotek.com/
 * @license Copyright 2017 Michael Mifsud
 */
class TestData extends Iface
{






    // ******************************************* //

    public function createUniqueEmail()
    {
        return hash('crc32', microtime()) . '@' . $this->createDomain();
    }

    public function createEmail()
    {
        return strtolower($this->createName()) . '@' . $this->createDomain();
    }

    public function createWww()
    {
        return  'http://www.' . $this->createDomain() . '/';
    }

    public function createPhone()
    {
        return  sprintf('%s %s %s', $this->createNumberStr(2), $this->createNumberStr(4), $this->createNumberStr(4));
    }

    public function createDomain()
    {
        return  strtolower($this->createName()).'.com' . ((rand(1,5)==1) ? '.au' : '');
    }

    public function createCourseName()
    {
        $names = array('VETS', 'AG', 'SCI', 'DVM');
        return $names[rand(0, count($names)-1)] . '-' . $this->createNumberStr(6);
    }

    public function createFullName()
    {
        return $this->createName() . ' ' . $this->createName();
    }
    
    public function createName()
    {
        $names = array('Andy','Bart','Charles','Denny','Eveline','Femke','Gismo','Harold','Imke','Jan','Kees','Lissane','Mark','Norris','Opa','Pieter','Quebec','Ralf','Stephen','Tamara','Ursula','Verdinant','Willem','Xanté','Yankee','Zuly');
        return $names[rand(0, count($names)-1)];
    }

    public function createNumberStr($len = 8)
    {
        $str = array();
        for ($i = 0; $i < $len ;$i++) {
            $str[] = rand(0, 9);
        }
        return implode('', $str);
    }

    public function createLipsumStr()
    {
        $str = <<<STR
Lorem ipsum dolor sit amet, consectetur adipiscing elit. Quisque venenatis id ex a hendrerit. Fusce dictum quis felis vel cursus. Proin sollicitudin sed justo ut accumsan. Integer tincidunt lacus nibh, quis viverra turpis ultrices eget. Donec in enim et nibh faucibus laoreet. Curabitur aliquam purus vitae luctus vestibulum. Donec facilisis augue vitae lorem gravida ornare. Pellentesque habitant morbi tristique senectus et netus et malesuada fames ac turpis egestas.

Fusce sed erat a odio eleifend iaculis vel non urna. Quisque consequat nunc quam, sit amet tincidunt dolor suscipit vel. Fusce id elit ligula. In ut augue purus. Aenean eu molestie ipsum. Sed porta eros quis efficitur euismod. Maecenas nec erat dictum, scelerisque elit at, ornare quam. Pellentesque et feugiat neque.
STR;
        return $str;
    }

    public function createLipsumHtml()
    {
        $str = <<<STR
<p>Lorem ipsum dolor sit amet, consectetur adipiscing elit. Quisque venenatis id ex a hendrerit. Fusce dictum quis felis vel cursus. Proin sollicitudin sed justo ut accumsan. Integer tincidunt lacus nibh, quis viverra turpis ultrices eget. Donec in enim et nibh faucibus laoreet. Curabitur aliquam purus vitae luctus vestibulum. Donec facilisis augue vitae lorem gravida ornare. Pellentesque habitant morbi tristique senectus et netus et malesuada fames ac turpis egestas.</p>
<p>Fusce sed erat a odio eleifend iaculis vel non urna. Quisque consequat nunc quam, sit amet tincidunt dolor suscipit vel. Fusce id elit ligula. In ut augue purus. Aenean eu molestie ipsum. Sed porta eros quis efficitur euismod. Maecenas nec erat dictum, scelerisque elit at, ornare quam. Pellentesque et feugiat neque.</p>
STR;
        return $str;
    }

    public function createBreed()
    {
        $names = array('Affenpinscher', 'Afghan Hound', 'Airedale Terrier', 'Akita', 'Alaskan Klee Kai', 'Alaskan Malamute', 'American Bulldog',
            'American English Coonhound', 'American Eskimo Dog', 'American Foxhound', 'American Pit Bull Terrier', 'American Staffordshire Terrier',
            'American Water Spaniel', 'Anatolian Shepherd Dog', 'Appenzeller Sennenhunde', 'Australian Cattle Dog', 'Australian Kelpie',
            'Australian Shepherd', 'Australian Terrier', 'Azawakh', 'Barbet', 'Basenji', 'Basset Hound', 'Beagle', 'Bearded Collie', 'Bedlington Terrier',
            'Belgian Malinois', 'Belgian Sheepdog', 'Belgian Tervuren', 'Berger Picard', 'Bernedoodle', 'Bernese Mountain Dog', 'Bichon Frise',
            'Black and Tan Coonhound', 'Black Mouth Cur', 'Black Russian Terrier', 'Bloodhound', 'Blue Lacy', 'Bluetick Coonhound', 'Boerboel',
            'Bolognese', 'Border Collie', 'Border Terrier', 'Borzoi', 'Boston Terrier', 'Bouvier des Flandres', 'Boxer', 'Boykin Spaniel',
            'Bracco Italiano', 'Briard', 'Brittany', 'Brussels Griffon', 'Bull Terrier', 'Bulldog', 'Bullmastiff', 'Cairn Terrier', 'Canaan Dog',
            'Cane Corso', 'Cardigan Welsh Corgi', 'Catahoula Leopard Dog', 'Caucasian Shepherd Dog', 'Cavalier King Charles Spaniel', 'Cesky Terrier',
            'Chesapeake Bay Retriever', 'Chihuahua', 'Chinese Crested', 'Chinese Shar-Pei', 'Chinook', 'Chow Chow', 'Clumber Spaniel', 'Cockapoo',
            'Cocker Spaniel', 'Collie', 'Coton de Tulear', 'Curly-Coated Retriever', 'Dachshund', 'Dalmatian', 'Dandie Dinmont Terrier', 'Doberman Pinscher',
            'Dogo Argentino', 'Dogue de Bordeaux', 'Dutch Shepherd', 'English Cocker Spaniel', 'English Foxhound', 'English Setter',
            'English Springer Spaniel', 'English Toy Spaniel', 'Entlebucher Mountain Dog', 'Field Spaniel', 'Finnish Lapphund', 'Finnish Spitz',
            'Flat-Coated Retriever', 'Fox Terrier', 'French Bulldog', 'German Pinscher', 'German Shepherd Dog', 'German Shorthaired Pointer',
            'German Wirehaired Pointer', 'Giant Schnauzer', 'Glen of Imaal Terrier', 'Goldador', 'Golden Retriever', 'Goldendoodle', 'Gordon Setter',
            'Great Dane', 'Great Pyrenees', 'Greater Swiss Mountain Dog', 'Greyhound', 'Harrier', 'Havanese', 'Ibizan Hound', 'Icelandic Sheepdog',
            'Irish Red and White Setter', 'Irish Setter', 'Irish Terrier', 'Irish Water Spaniel', 'Irish Wolfhound', 'Italian Greyhound', 'Jack Russell Terrier',
            'Japanese Chin', 'Japanese Spitz', 'Karelian Bear Dog', 'Keeshond', 'Kerry Blue Terrier', 'Komondor', 'Kooikerhondje', 'Korean Jindo Dog', 'Kuvasz',
            'Labradoodle', 'Labrador Retriever', 'Lakeland Terrier', 'Lancashire Heeler', 'Leonberger', 'Lhasa Apso', 'Lowchen', 'Maltese', 'Maltese Shih Tzu',
            'Maltipoo', 'Manchester Terrier', 'Mastiff', 'Miniature Pinscher', 'Miniature Schnauzer', 'Mudi', 'Mutt', 'Neapolitan Mastiff', 'Newfoundland',
            'Norfolk Terrier', 'Norwegian Buhund', 'Norwegian Elkhound', 'Norwegian Lundehund', 'Norwich Terrier', 'Nova Scotia Duck Tolling Retriever',
            'Old English Sheepdog', 'Otterhound', 'Papillon', 'Peekapoo', 'Pekingese', 'Pembroke Welsh Corgi', 'Petit Basset Griffon Vendeen',
            'Pharaoh Hound', 'Plott', 'Pocket Beagle', 'Pointer', 'Polish Lowland Sheepdog', 'Pomeranian', 'Pomsky', 'Poodle', 'Portuguese Water Dog',
            'Pug', 'Puggle', 'Puli', 'Pyrenean Shepherd', 'Rat Terrier', 'Redbone Coonhound', 'Rhodesian Ridgeback', 'Rottweiler', 'Saint Bernard',
            'Saluki', 'Samoyed', 'Schipperke', 'Schnoodle', 'Scottish Deerhound', 'Scottish Terrier', 'Sealyham Terrier', 'Shetland Sheepdog', 'Shiba Inu',
            'Shih Tzu', 'Siberian Husky', 'Silky Terrier', 'Skye Terrier', 'Sloughi', 'Small Munsterlander Pointer', 'Soft Coated Wheaten Terrier', 'Stabyhoun',
            'Staffordshire Bull Terrier', 'Standard Schnauzer', 'Sussex Spaniel', 'Swedish Vallhund', 'Tibetan Mastiff', 'Tibetan Spaniel', 'Tibetan Terrier',
            'Toy Fox Terrier', 'Treeing Tennessee Brindle', 'Treeing Walker Coonhound', 'Vizsla', 'Weimaraner', 'Welsh Springer Spaniel', 'Welsh Terrier',
            'West Highland White Terrier', 'Whippet', 'Wirehaired Pointing Griffon', 'Xoloitzcuintli', 'Yorkipoo', 'Yorkshire Terrier');
        return $names[rand(0, count($names)-1)];
    }

    public function createActivityName()
    {
        $names = array(
            'Ultrasound CE workshop',
            'Ultrasound DVM teaching',
            'Neurology Practical',
            'Cardiology - ECG Practical',
            'Reproduction Practical',
            'Health Check - Routine Care',
            'Exercise / Walking',
            'Foster stay',
            'Gastrointestinal Practical',
            'Ophthalmology Practical',
            'Dermatology Practical',
            'Communications Practical'
        );
        return $names[rand(0, count($names)-1)];
    }


}

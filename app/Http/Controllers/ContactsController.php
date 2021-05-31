<?php

namespace App\Http\Controllers;

use App\Models\Contact;
use App\Models\Email;
use App\Models\PhoneNumber;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class ContactsController extends Controller
{

    private function importVCF($file_path, $user_id)
    {

        // return getContactsFromVCF($file_path);
        $vcards = getContactsFromVCF($file_path);

        foreach ($vcards as $vcard) {

            if (empty($vcard->fn)) {
                continue;
            }

            $name = explode(" ", $vcard->fn);
            $contact = new Contact([
                'first_name' => $name[0],
                'last_name' => join(' ', array_slice($name, 1)),
            ]);

            if (!empty($vcard->photo)) {
                $contact->avatar = $vcard->photo;
            }

            if (!empty($vcard->note)) {
                $contact->note = $vcard->note;
            }

            $contact->user_id = $user_id;
            $contact->save();

            if (isset($vcard->phone_numbers)) {
                $phone_numbers = array_map(
                    function ($number) {
                        return new PhoneNumber($number);
                    },
                    $vcard->phone_numbers
                );
                $contact->phone_numbers()->saveMany($phone_numbers);
            }

            if (isset($vcard->emails)) {
                $emails = array_map(
                    function ($email) {
                        return new Email($email);
                    },
                    $vcard->emails
                );
                $contact->emails()->saveMany($emails);
            }
        }

    }

    private function importCSV($file_path, $user_id)
    {
        $csv_array = csv_to_array($file_path);
        foreach ($csv_array as $csv_contact) {
            $name = explode(" ", $csv_contact['Name']);
            $contact = new Contact([
                'first_name' => $name[0],
                'last_name' => join(' ', array_slice($name, 1)),
            ]);

            if (!empty($csv_contact['Photo'])) {
                $contact->avatar = $csv_contact['Photo'];
            }

            if (!empty($csv_contact['Notes'])) {
                $contact->note = $csv_contact['Notes'];
            }

            if (!empty($csv_contact['Occupation'])) {
                $contact->job_title = $csv_contact['Occupation'];
            }

            $contact->user_id = $user_id;
            $contact->save();

            $phone_numbers = [];
            foreach (range(1, 5) as $i) {
                if (!empty($csv_contact['Phone ' . $i . ' - Value'])) {
                    $m_numbers = explode(':::', $csv_contact['Phone ' . $i . ' - Value']);
                    foreach ($m_numbers as $number) {
                        $phone_numbers[] = new PhoneNumber([
                            'label' => $csv_contact['Phone ' . $i . ' - Type'],
                            'phone_number' => trim($number),
                        ]);
                    }
                }
            }

            $emails = [];
            foreach (range(1, 3) as $i) {
                if (!empty($csv_contact['E-mail ' . $i . ' - Value'])) {
                    $m_emails = explode(":::", $csv_contact['E-mail ' . $i . ' - Value']);
                    foreach ($m_emails as $email) {
                        $emails[] = new Email([
                            'label' => $csv_contact['E-mail ' . $i . ' - Type'],
                            'email' => trim($email),
                        ]);
                    }
                }
            }

            if (count($phone_numbers) > 0) {
                $contact->phone_numbers()->saveMany($phone_numbers);
            }

            if (count($emails) > 0) {
                $contact->emails()->saveMany($emails);
            }
        }
    }

    public function import(Request $request)
    {
        $file = $request->file('file');
        $extension = $file->getClientOriginalExtension();
        if (!in_array($extension, ['csv', 'vcf'])) {
            return response()->json([
                'errors' => [
                    'file' => ['File have to be csv or vcf.'],
                ],
                '$extension' => $extension,
                'message' => 'Invalid file type.',
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $user = Auth::user();

        $path = $file->getRealPath();

        if ($extension == 'csv') {
            $this->importCSV($path, $user->id);
        } else if ($extension == 'vcf') {
            $this->importVCF($path, $user->id);
            // return response()->json([
            //     'contacts' => $this->importVCF($path, $user->id),
            //     'message' => 'Contacts imported',
            // ], Response::HTTP_OK);
        }

        $per_page = 1000;
        $contacts = Contact::where('user_id', $user->id)
            ->orderBy('first_name', 'asc')
            ->with(['phone_numbers', 'emails'])
            ->paginate($per_page);

        return response()->json([
            'contacts' => $contacts,
            'message' => 'Contacts imported',
        ], Response::HTTP_OK);
    }

    public function insert(Request $request)
    {
        $user = Auth::user();
        if ($request->id && $user->role != 'admin') {
            return response()->json([
                'message' => 'Permission denied.',
            ], Response::HTTP_UNAUTHORIZED);
        }

        $validation = Validator::make($request->all(), [
            'first_name' => 'required|max:16',
            'last_name' => 'max:16',
            'phone_numbers' => 'required|array|min:1',
            'emails.*.email' => 'required|email',
            'phone_numbers.*.phone_number' => 'required',
        ]);

        if ($validation->fails()) {
            return response()->json([
                'errors' => $validation->errors(),
                'message' => 'Contact validation failed.',
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        };

        $new_contact = new Contact(
            $request->only(
                'first_name', 'last_name',
                'company', 'job_title',
                'is_favorite', 'note'
            )
        );
        $new_contact->user_id = non_empty($request->id, $user->id);
        $new_contact->save();

        $phone_numbers = array_map(
            function ($phone_number) {
                return new PhoneNumber($phone_number);
            },
            $request->phone_numbers
        );
        $new_contact->phone_numbers()->saveMany($phone_numbers);

        if ($request->emails) {
            $emails = array_map(
                function ($email) {
                    return new Email($email);
                },
                $request->emails
            );
            $new_contact->emails()->saveMany($emails);
        }

        $saved_contact = Contact::with(['phone_numbers', 'emails'])->find($new_contact->id);

        return response()->json([
            'contact' => $saved_contact,
            'message' => 'Contact saved successfully',
        ], Response::HTTP_CREATED);

    }

    public function index(Request $request)
    {
        $user = Auth::user();
        if ($request->id && $user->role != 'admin') {
            return response()->json([
                'message' => 'Permission denied.',
            ], Response::HTTP_UNAUTHORIZED);
        }

        if ($request->id && !User::find($request->id)) {
            return response()->json([
                'message' => 'User not found',
            ], Response::HTTP_NOT_FOUND);
        }

        $per_page = 50;
        $contacts = Contact::where('user_id', non_empty($request->id, $user->id))
            ->orderBy('first_name', 'asc')
            ->with(['phone_numbers:id,contact_id,label,country_code,phone_number', 'emails:id,contact_id,email,label'])
            ->select(['id', 'first_name', 'last_name', 'avatar'])
            ->paginate($per_page);

        return response()->json(['contacts' => $contacts], Response::HTTP_OK);

    }

    public function getTrashed()
    {
        $per_page = 1000;
        $user = Auth::user();
        $contacts = Contact::onlyTrashed()
            ->where('user_id', $user->id)
            ->orderBy('first_name', 'asc')
            ->with(['phone_numbers:id,contact_id,label,country_code,phone_number', 'emails:id,contact_id,email,label'])
            ->select(['id', 'first_name', 'last_name', 'avatar'])
            ->paginate($per_page);

        return response()->json([
            'contacts' => $contacts,
        ], Response::HTTP_OK);

    }


    public function select(Request $request)
    {
        $user = Auth::user();
        if ($request->id && $user->role != 'admin') {
            return response()->json([
                'message' => 'Permission denied.',
            ], Response::HTTP_UNAUTHORIZED);
        }

        if ($request->id && !User::find($request->id)) {
            return response()->json([
                'message' => 'User not found',
            ], Response::HTTP_NOT_FOUND);
        }

        $contact = Contact::where([
            'id' => $request->contact_id,
            'user_id' => non_empty($request->id, $user->id),
        ])
            ->with(['phone_numbers', 'emails'])
            ->first();

        if (!$contact) {
            return response()->json([
                'message' => 'Contact not found!',
            ], Response::HTTP_NOT_FOUND);
        }

        return response()->json(['contact' => $contact], Response::HTTP_OK);
    }

    public function update(Request $request, ...$args)
    {

        $id = count($args) > 1 ? $args[0] : null;
        $contact_id = count($args) == 1 ? $args[0] : $args[1];

        $user = Auth::user();
        if ($id && $user->role != 'admin') {
            return response()->json([
                'message' => 'Permission denied.',
            ], Response::HTTP_UNAUTHORIZED);
        }

        if ($id && !User::find($id)) {
            return response()->json([
                'message' => 'User not found',
            ], Response::HTTP_NOT_FOUND);
        }

        $validation = Validator::make($request->all(), [
            'last_name' => 'max:16',
            'first_name' => 'required|max:16',
            'phone_numbers' => 'required|array|min:1',
            'emails.*.email' => 'required|email',
            'phone_numbers.*.phone_number' => 'required',
        ]);

        if ($validation->fails()) {
            return response()->json([
                'errors' => $validation->errors(),
                'message' => 'Contact validation failed.',
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        };

        $contact = Contact::where([
            'id' => $contact_id,
            'user_id' => non_empty($id, $user->id),
        ])
            ->select('id')
            ->first();

        if (!$contact) {
            return response()->json([
                'message' => 'Contact not found!',
            ], Response::HTTP_NOT_FOUND);
        }

        $contact->update(
            $request->only(
                'first_name', 'last_name',
                'company', 'job_title',
                'is_favorite', 'note'
            )
        );

        $contact->phone_numbers()->delete();
        $contact->phone_numbers()->saveMany(
            array_map(
                function ($phone_number) {
                    return new PhoneNumber($phone_number);
                },
                $request->phone_numbers
            )
        );

        if ($request->emails) {
            $contact->emails()->delete();
            $contact->emails()->saveMany(
                array_map(
                    function ($email) {
                        return new Email($email);
                    },
                    $request->emails
                )
            );
        }

        $contact = Contact::where([
            'id' => $contact_id,
            'user_id' => non_empty($id, $user->id),
        ])
            ->with(['phone_numbers', 'emails'])
            ->first();

        return response()->json(['contact' => $contact], Response::HTTP_OK);
    }

    public function moveToTrash(Request $request)
    {
        Contact::whereIn('id', $request->ids)->delete();
        return response()->json([
            'message' => 'Contacts moved to trash.',
        ], Response::HTTP_OK);
    }

    public function restoreFromTrash(Request $request)
    {
        Contact::withTrashed()
            ->whereIn('id', $request->ids)
            ->restore();
        return response()->json([
            'message' => 'Contacts has been restored.',
        ], Response::HTTP_OK);

    }

    public function deletePermanently(Request $request)
    {
        Contact::withTrashed()
            ->whereIn('id', $request->ids)
            ->forceDelete();
        return response()->json([
            'message' => 'Contacts permanently deleted.',
        ], Response::HTTP_OK);

    }
}

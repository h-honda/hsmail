<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

$string['hsmail:addinstance'] = 'HSメール追加';
$string['hsmail:addcondition'] = '条件追加権限';
$string['hsmail:viewmaillist'] = 'メールリスト表示';

$string['condition1'] = '一斉メール配信設定画面';
$string['hsmail_settings'] = 'HSメール設定画面';

$string['pluginname'] = '一斉メール配信';
$string['headerconfig'] = '共通設定';
$string['descconfig'] = '共通設定の説明';

$string['jobs_messeage'] = '登録されているジョブ {$a->num} 個';
$string['blocksettings'] = 'メール配信条件設定';
$string['transmissiontiming'] = '配信タイミング';
$string['assignment'] = '配信日時';
$string['now'] = '今すぐ';
$string['send_repeat'] = '繰り返し間隔(秒)';

$string['jobs'] = '設定されているメール';

$string['add'] = '配信メール登録';
$string['confirm'] = '配信予約済みメール確認';

$string['sent_confirm'] = '配信済みメール確認';
$string['perpage'] = 'ページ毎表示件数';
$string['user_perpage'] = 'ページ毎表示件数（学生）';
$string['err_already'] = 'すでに送信されたメールです';
$string['err_planid'] = 'プランIdが取得できません';
$string['sentlist'] = '配信済みメール一覧';
$string['joblist'] = '配信予約メール一覧';
$string['mailmax'] = '同時メール送信数';
$string['mailmax_desc'] = '１回のcronで送信するメール数を設定します';
$string['delete_confirm'] = '削除しますか';
$string['head_create'] = 'メール作成日';
$string['head_title'] = '条件タイトル';
$string['head_mail'] = 'メールタイトル';
$string['head_user'] = '作成者';
$string['head_sent'] = '配信予定日';
$string['head_action'] = 'アクション';
$string['head_sent_count'] = '配信対象ユーザ数';
$string['head_sent_start'] = '配信開始日時';
$string['head_sent_end'] = '配信完了日時';
$string['queue_count'] = '送信処理中メール数（{$a}）';
$string['queue_count_total'] = '送信処理中メール総数（サイト全体）';
$string['mailbody'] = 'メール本文';
$string['mailbody_help'] = 'メール本文中に以下のプレースフォルダを使用できます。<br>プレースフォルダはメール送信時に置換されます。<br>[[course_name]] ：コース名<br>[[user_name]] ：メール受信者の氏名';
$string['detail_mail'] = '送信内容';
$string['detail_condition'] = '配信条件';
$string['date_time_selector'] = '配信開始日';
$string['target'] = '配信対象';
$string['target_s'] = '希望者のみ';
$string['target_a'] = 'サイト利用者';
$string['target_c'] = 'コース受講者';
$string['complete'] = 'コース完了ステータス';
$string['complete_c'] = 'コース完了';
$string['complete_i'] = 'コース未完了';
$string['ignore_mailmax'] = '即時配信時に同時メール送信数を無視';
$string['ignore_mailmax_desc'] = '即時配信時に同時メール送信数の設定を無視して全メールを一度に送信します';
$string['instantly'] = '即時配信';
$string['meildetail'] = 'メール詳細';

$string['quizcomplete'] = '小テスト完了ステータス';
$string['quizcomplete_c'] = '小テスト完了';
$string['quizcomplete_i'] = '小テスト未完了';
$string['quizname'] = '小テスト名';
$string['quizcomplete_error'] = '小テスト名を一つ以上選択してください';

$string['assigncomplete'] = '課題完了ステータス';
$string['assigncomplete_c'] = '課題完了';
$string['assigncomplete_i'] = '課題未完了';
$string['assignname'] = '課題名';
$string['assigncomplete_error'] = '課題名を一つ以上選択してください';

$string['forumcomplete'] = 'フォーラム完了ステータス';
$string['forumcomplete_c'] = 'フォーラム完了';
$string['forumcomplete_i'] = 'フォーラム未完了';
$string['forumname'] = 'フォーラム名';
$string['forumcomplete_error'] = 'フォーラム名を一つ以上選択してください';

$string['coursedisaccess'] = '過去何日アクセスしていない人';

$string['footer'] = 'フッター';
$string['footer_desc'] = '全てのメール本文の最後に挿入されます';

$string['event_mail_added_desc'] = 'メールをキューに登録';
$string['event_mail_added'] = 'メール{$a}通をキューに登録';
$string['event_mail_sent_desc'] = 'hsmail送信';
$string['event_mail_sent'] = '{$a}通メール送信しました';

$string['task_addcue_sentmail'] = 'メールキュー＆送信スケジュール';
$string['days'] = '日';

import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';

import '../auth/cubit/auth_cubit.dart';
import '../auth/models/user.dart';

/// App bar with the user's name and a log-out button, shared by both home screens.
class HomeAppBar extends StatelessWidget implements PreferredSizeWidget {
  const HomeAppBar({super.key, required this.title, required this.user});

  final String title;
  final User user;

  @override
  Size get preferredSize => const Size.fromHeight(kToolbarHeight);

  @override
  Widget build(BuildContext context) {
    return AppBar(
      title: Text(title),
      actions: [
        PopupMenuButton<void>(
          tooltip: 'Account',
          icon: const Icon(Icons.account_circle_outlined),
          itemBuilder: (context) => [
            PopupMenuItem(enabled: false, child: Text('${user.name}\n${user.universityId}')),
            const PopupMenuDivider(),
            PopupMenuItem(
              onTap: context.read<AuthCubit>().logOut,
              child: const ListTile(
                leading: Icon(Icons.logout),
                title: Text('Log out'),
                contentPadding: EdgeInsets.zero,
              ),
            ),
          ],
        ),
      ],
    );
  }
}
